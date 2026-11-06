
# Distribution and Marketplace Submission Plan

**Planning status:** Distribution routes and release gates are documented. No listing or submission has been created.  
**Sources reviewed:** Official WordPress.org and WooCommerce Marketplace docs, 2026-11-06.

## 1. Two editions, two submission tracks

| Edition | Destination | Submission model | User-facing purpose |
|---|---|---|---|
| Free | WordPress.org Plugin Directory | Complete, production-ready plugin ZIP; after approval, maintain releases through the directory's distribution process | Structured reason collection and basic reports |
| Paid Pro | WooCommerce.com Marketplace | Separate Paid submission and review; freemium Free/Paid submissions can be merged on one product page after approval | Advanced product/variation insights, clustering, alerts, corrective-action tracking |

A shared codebase/package architecture is a product decision for P6. Regardless of implementation, the Free package must work independently, the Paid package must not duplicate/conflict with Free, and license expiry must not erase merchant data.

## 2. WordPress.org Free submission gate

Before applying:

- Create a complete, usable, production-ready ZIP. WordPress.org does not reserve plugin names for incomplete concepts.
- Confirm all plugin code, bundled libraries, images, and other submitted assets are GPL-compatible; document attribution and license sources.
- Prepare the plugin header, stable plugin name/slug, readme.txt, changelog, screenshots, FAQ/support route, and accurate tested-up-to metadata.
- Keep source human-readable and public; do not obfuscate code.
- Review security, privacy, external-service disclosure, trademark, and directory guidelines.
- Confirm the ZIP is within the currently documented size limit and contains no unnecessary development files, secrets, or unbuilt assets.
- Submit with an up-to-date developer account/contact email and respond to review feedback.
- After approval, maintain the directory version and follow the current release/tagging process.

## 3. WooCommerce.com Paid submission gate

Before applying/submitting:

- Apply for and receive approval for a Woo Marketplace vendor account.
- Prepare product rationale, competition analysis, business/pricing model, product details, demo, support/documentation, and clear testing instructions.
- Review the vendor agreement, revenue-share/commercial terms, name/trademark rules, and current pricing expectations before committing.
- Submit the Paid package separately from Free. Woo's current freemium process uses two submissions and can merge them into one product page after approval.
- Support the two latest major releases of both WordPress and WooCommerce at submission time; recalculate the compatibility matrix then.
- Pass the required Quality Insights Toolkit checks: API, E2E, Activation, Security, PHPCompatibility, Malware, and Validation.
- Demonstrate HPOS compatibility, WordPress accessibility standards, current WooCommerce UX patterns, and compatibility with major Woo extensions.
- Document critical flows and provide reproduction/test instructions. Resolve every submission failure and reviewer request before launch.

The dated WordPress 7.1/7.0 and WooCommerce 11.2/11.1 series in docs/COMPATIBILITY.md are planning targets, not evidence of Marketplace compliance. Marketplace's moving two-major-version rule takes precedence; refresh the matrix at submission.

## 4. Release packaging and versioning

- Maintain one source of truth and produce separate Free and Paid release artifacts.
- Include compiled React assets in the shipped packages; merchants should not need Node/npm or CLI tooling.
- Exclude tests, development dependencies, source maps/secrets where inappropriate, and unused files from production ZIPs; retain readable source and build instructions in the development repository.
- Use coordinated versioning and a compatibility table for Free/Pro pairing. Define behavior when only Free is installed, when Pro is updated before Free, and when a license expires.
- Provide a changelog, upgrade/rollback guidance, data migration policy, support route, and release verification record for every public release.
- Run the required security, activation, API, E2E, PHP compatibility, malware, and validation checks before each Marketplace submission/update, according to then-current vendor requirements.

## 5. Proposed release sequence

1. Complete P6 product and implementation readiness decisions.
2. Build the complete Free and Pro editions and finish the compatibility/security/accessibility matrix.
3. Prepare WordPress.org Free package and listing; submit and address review feedback.
4. Apply for Woo Marketplace vendor approval and submit the Paid package separately.
5. Resolve automated and human review findings; do not announce approval before both channels confirm.
6. Coordinate listing pages, versions, support, pricing, and release notes.
7. Maintain both tracks, keeping Free current and compatibility disclosures accurate.

**Timing:** Actual submission dates remain TBD until the plugin is complete and passes release checks. No submission, vendor application, or listing action is being performed by this plan update.

## 6. Official sources reviewed 2026-11-06

- [WordPress.org detailed plugin guidelines](https://developer.wordpress.org/plugins/wordpress-org/detailed-plugin-guidelines/)
- [WordPress.org planning, submitting, and maintaining plugins](https://developer.wordpress.org/plugins/wordpress-org/planning-submitting-and-maintaining-plugins/)
- [WordPress.org plugin developer FAQ](https://developer.wordpress.org/plugins/wordpress-org/plugin-developer-faq/)
- [Woo Marketplace product submission guidelines](https://developer.woocommerce.com/docs/woo-marketplace/submitting-your-product)
- [Woo Marketplace getting started](https://developer.woocommerce.com/docs/woo-marketplace/getting-started)
