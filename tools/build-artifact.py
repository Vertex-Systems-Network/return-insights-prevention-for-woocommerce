#!/usr/bin/env python3
"""Build an installable WooCommerce plugin ZIP with Composer's PSR-4 loader."""

from __future__ import annotations

import argparse
import hashlib
import os
import re
import shutil
import subprocess
import sys
import tempfile
import time
import zipfile
from datetime import datetime, timezone
from pathlib import Path
from typing import NoReturn


ROOT = Path(__file__).resolve().parents[1]
SLUG = "wpessential-return-insights-prevention-woocommerce"
ENTRY = f"{SLUG}.php"
NAMESPACE = "WPEssential\\ReturnInsights\\"
PACKAGE_PATHS = ("assets", "includes")
PACKAGE_FILES = (ENTRY, "composer.json", "readme.txt", "uninstall.php")


def fail(message: str) -> NoReturn:
    print(f"Build failed: {message}", file=sys.stderr)
    sys.exit(1)


def run(command: list[str], cwd: Path) -> str:
    try:
        result = subprocess.run(
            command,
            cwd=cwd,
            check=True,
            capture_output=True,
            text=True,
        )
    except FileNotFoundError:
        fail(f"Required executable not found: {command[0]}")
    except subprocess.CalledProcessError as error:
        if error.stdout:
            print(error.stdout, file=sys.stderr, end="")
        if error.stderr:
            print(error.stderr, file=sys.stderr, end="")
        fail(f"Command exited with status {error.returncode}: {' '.join(command)}")
    return result.stdout.strip()


def plugin_version() -> str:
    entry = ROOT / ENTRY
    if not entry.is_file():
        fail(f"Plugin entry file is missing: {entry}")
    match = re.search(r"^\s*\*\s*Version:\s*([0-9A-Za-z.+-]+)\s*$", entry.read_text(encoding="utf-8"), re.MULTILINE)
    if not match:
        fail("Could not read the plugin Version header")
    version = match.group(1)
    readme = (ROOT / "readme.txt").read_text(encoding="utf-8")
    stable_tag = re.search(r"^Stable tag:\s*(\S+)\s*$", readme, re.MULTILINE)
    if not stable_tag or stable_tag.group(1) != version:
        fail("Plugin header Version and readme.txt Stable tag must match")
    return version


def copy_source(stage: Path) -> None:
    for relative in PACKAGE_FILES:
        source = ROOT / relative
        if not source.is_file():
            fail(f"Required package file is missing: {relative}")
        shutil.copy2(source, stage / relative)

    for relative in PACKAGE_PATHS:
        source = ROOT / relative
        if not source.is_dir():
            fail(f"Required package directory is missing: {relative}")
        if any(path.is_symlink() for path in source.rglob("*")):
            fail(f"Symlinks are not allowed in package source: {relative}")
        shutil.copytree(source, stage / relative, ignore=shutil.ignore_patterns(".*", "*.map", "__pycache__"))


def generate_autoloader(stage: Path) -> None:
    php_version = run(["php", "-r", "echo PHP_VERSION_ID;"], stage)
    if not php_version.isdigit() or int(php_version) < 80300:
        fail("PHP 8.3 or newer is required to generate the Composer autoloader")

    composer = shutil.which("composer")
    if not composer:
        fail("Composer 2 is required; install it from getcomposer.org and retry")
    composer_version = run([composer, "--version"], stage)
    if not re.search(r"\bComposer version 2\.", composer_version):
        fail("Composer 2 is required to generate the PSR-4 autoloader")

    run(
        [
            composer,
            "install",
            "--no-dev",
            "--prefer-dist",
            "--no-interaction",
            "--no-progress",
            "--classmap-authoritative",
        ],
        stage,
    )

    autoload = stage / "vendor" / "autoload.php"
    psr4_map = stage / "vendor" / "composer" / "autoload_psr4.php"
    if not autoload.is_file() or not psr4_map.is_file():
        fail("Composer did not generate vendor/autoload.php and its PSR-4 map")
    composer_namespace = NAMESPACE.replace("\\", "\\\\")
    if composer_namespace not in psr4_map.read_text(encoding="utf-8"):
        fail("Generated Composer autoloader is missing the plugin PSR-4 namespace")


def package_zip(stage: Path, output: Path, version: str) -> Path:
    output.mkdir(parents=True, exist_ok=True)
    archive = output / f"{SLUG}-{version}.zip"
    try:
        epoch = int(os.environ.get("SOURCE_DATE_EPOCH", str(int(time.time()))))
        stamp = datetime.fromtimestamp(epoch, tz=timezone.utc)
    except (OverflowError, OSError, ValueError):
        fail("SOURCE_DATE_EPOCH must be a valid Unix timestamp")
    stamp = max(stamp, datetime(1980, 1, 1, tzinfo=timezone.utc))
    zip_time = (stamp.year, stamp.month, stamp.day, stamp.hour, stamp.minute, stamp.second)

    with zipfile.ZipFile(archive, "w", compression=zipfile.ZIP_DEFLATED, compresslevel=9) as bundle:
        for source in sorted(stage.rglob("*")):
            if not source.is_file():
                continue
            if source.is_symlink():
                fail(f"Refusing to package symlink: {source.relative_to(stage)}")
            relative = source.relative_to(stage).as_posix()
            info = zipfile.ZipInfo(f"{SLUG}/{relative}", date_time=zip_time)
            info.compress_type = zipfile.ZIP_DEFLATED
            info.external_attr = 0o100644 << 16
            bundle.writestr(info, source.read_bytes(), compress_type=zipfile.ZIP_DEFLATED, compresslevel=9)

    with zipfile.ZipFile(archive) as bundle:
        names = set(bundle.namelist())
        required = {f"{SLUG}/{ENTRY}", f"{SLUG}/vendor/autoload.php", f"{SLUG}/includes/Plugin.php"}
        missing = sorted(required - names)
        if missing:
            archive.unlink(missing_ok=True)
            fail(f"ZIP is missing required runtime files: {', '.join(missing)}")
        if any(name.startswith(f"{SLUG}/docs/") or "/.git/" in name for name in names):
            archive.unlink(missing_ok=True)
            fail("Development documentation or Git metadata was included in the ZIP")

    digest = hashlib.sha256(archive.read_bytes()).hexdigest()
    (archive.with_suffix(archive.suffix + ".sha256")).write_text(f"{digest}  {archive.name}\n", encoding="utf-8")
    return archive


def main() -> int:
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("--output-dir", type=Path, default=ROOT / "dist", help="Directory for the ZIP and SHA-256 file (default: dist/)")
    args = parser.parse_args()
    version = plugin_version()

    with tempfile.TemporaryDirectory(prefix=f"{SLUG}-build-") as temporary:
        stage = Path(temporary) / SLUG
        stage.mkdir(parents=True)
        copy_source(stage)
        generate_autoloader(stage)
        archive = package_zip(stage, args.output_dir.resolve(), version)

    print(f"Artifact: {archive}")
    print(f"SHA-256: {archive.with_suffix(archive.suffix + '.sha256')}")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())