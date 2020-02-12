# Noptin - Fastest WordPress Newsletter Plugin

<p align="center">
    <a href="https://github.com/hizzle-co/noptin/graphs/contributors" alt="Contributors">
        <img src="https://img.shields.io/github/contributors/hizzle-co/noptin" /></a>
    <a href="https://github.com/hizzle-co/noptin/pulse" alt="Activity">
        <img src="https://img.shields.io/github/commit-activity/m/hizzle-co/noptin" /></a>
    <img src="https://img.shields.io/github/commits-since/hizzle-co/noptin/latest?label=Commits%20To%20Deploy" alt="Commits to deploy">
    <a href="https://travis-ci.org/hizzle-co/noptin">
        <img src="https://img.shields.io/travis/hizzle-co/noptin/master" alt="build status"></a>
    <img src="https://img.shields.io/github/languages/count/hizzle-co/noptin" alt="languages">
    <img src="https://img.shields.io/github/languages/code-size/hizzle-co/noptin" alt="code size">
    <img src="https://img.shields.io/github/repo-size/hizzle-co/noptin" alt="repo size">
    <img src="https://img.shields.io/wordpress/plugin/dm/newsletter-optin-box" alt="Monthly Downloads">
    <a href="https://www.gnu.org/licenses/gpl-3.0.en.html">
        <img src="https://img.shields.io/github/license/hizzle-co/noptin" alt="License"></a>
    <a href="https://wordpress.org/support/plugin/newsletter-optin-box/reviews/">
        <img src="https://img.shields.io/wordpress/plugin/stars/newsletter-optin-box" alt="Rating"></a>
    <img src="https://img.shields.io/wordpress/plugin/v/newsletter-optin-box?label=version" alt="Version">
    <a href="https://noptin.com">
        <img src="https://img.shields.io/website?url=https%3A%2F%2Fnoptin.com" alt="Website"></a>
</p>

Welcome to the Noptin repository on GitHub. Here, you can [report bugs](https://github.com/hizzle-co/noptin/issues/new?assignees=&labels=&template=bug_report.md&title=), [request for new features and enhancements](https://github.com/hizzle-co/noptin/issues/new?assignees=&labels=&template=feature_request.md&title=) and follow the development of the plugin.

If you want to install this plugin on a live website, please use the [Noptin plugin page on WordPress.org](https://wordpress.org/plugins/newsletter-optin-box/).

## Contributing

Contributing isn't just writing code - it's anything that improves the project. All contributions for Noptin are managed right here on GitHub. Here are some ways you can help:

### Reporting bugs

If you're running into an issue with the plugin, please use our [issue tracker](https://github.com/hizzle-co/noptin/issues/new?assignees=&labels=&template=bug_report.md&title=) to open a new issue. If you're able, include steps to reproduce, environment information, and screenshots/screencasts as relevant. *Do not use the issue tracker for support requests. For that, checkout our [contact form](https://noptin.com/contact/).*

### Suggesting enhancements

New features and enhancements are also managed via [issues](https://github.com/hizzle-co/noptin/issues/new?assignees=&labels=&template=feature_request.md&title=).

### Write and submit a patch

If you'd like to fix a bug or make an enhancement, you can submit a Pull Request. To do this:-

1. [Fork](https://help.github.com/en/github/getting-started-with-github/fork-a-repo) this repo on GitHub.
2. Make the changes you want to submit.
4. [Create a new pull request](https://help.github.com/en/articles/creating-a-pull-request-from-a-fork).

By contributing your code, you are helping us create a better, more reliable WordPress plugin. As a result, your website (along with hundreds of other websites) can benefit from having better newsletter subscription forms.

## Workflow

The `master` branch is the development branch which means it contains the next version to be released. `stable` contains the current latest release and `stable-dev` contains the corresponding stable development version. Always open up PRs against `master`.

## Release instructions

1. **Merge changes:** Merge all changes into `master` and ensure that [Travis CI](https://travis-ci.org/hizzle-co/noptin) does not produce any fatal errors.
2. **Version bump:** Bump the version numbers in `noptin.php` and `readme.txt` if it does not already reflect the version being released.
3. **Update Changelog:** Update the changelog in `readme.txt`
4. **Localize:** Update the language files.
5. **Clean:** Check to be sure any new files/paths that are unnecessary in the production version are included in `.gitattributes`.
6. **Merge into stable:** Make a non-fast-forward merge from `master` into `stable`. `stable` contains the latest stable version.
9. **Test:** Check out the `stable` branch and test for functionality locally.
10. **Release on GitHub:** Create a new GitHub release and copy the changelog for the release into the Release body. Create a tag for the release as `X.Y.Z` then publish the release. Users will now be able to update the plugin from their WordPress dashboards.


## Support Level

**Active:** Noptin is actively working on this, and we expect to continue work for the foreseeable future including keeping tested up to the most recent version of WordPress.  Bug reports, feature requests, questions, and pull requests are welcome.
