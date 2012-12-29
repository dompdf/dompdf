# How to contribute

- [Getting help](#getting-help)
- [Submitting bug reports](#submitting-bug-reports)
- [Contributing code](#contributing-code)

## Getting help

Community discussion, questions, and informal bug reporting is done on the
[dompdf Google group](http://groups.google.com/group/dompdf).

## Submitting bug reports

The preferred way to report bugs is to use the
[GitHub issue tracker](http://github.com/dompdf/dompdf/issues). Before
reporting a bug, read these pointers.

**Please search inside the bug tracker to see if the bug you found is not already reported.**

**Note:** The issue tracker is for *bugs* and *feature requests*, not requests for help.
Questions should be asked on the
[dompdf Google group](http://groups.google.com/group/dompdf) instead.

### Reporting bugs effectively

- dompdf is maintained by volunteers. They don't owe you anything, so be
  polite. Reports with an indignant or belligerent tone tend to be moved to the
  bottom of the pile.

- Include information about **the PHP version on which the problem occurred**. Even
  if you tested several PHP version on different servers, and the problem occurred
  in all of them, mention this fact in the bug report.
  Also include the operating system it's installed on. PHP configuration can also help,
  and server error logs (like Apache logs)

- Mention which release of dompdf you're using (the zip, the master branch, etc).
  Preferably, try also with the current development snapshot, to ensure the
  problem has not already been fixed.

- Mention very precisely what went wrong. "X is broken" is not a good bug
  report. What did you expect to happen? What happened instead? Describe the
  exact steps a maintainer has to take to make the problem occur. We can not
  fix something that we can not observe.

- If the problem can not be reproduced in any of the demos included in the
  dompdf distribution, please provide an HTML document that demonstrates
  the problem. There are a few options to show us your code:
   - [JS Fiddle](http://jsfiddle.net/)
   - [dompdf debug helper](http://eclecticgeek.com/dompdf/debug.php) (provided by @bsweeney)
   - Include the HTML/CSS inside the bug report, with
   [code highlighting](https://github.com/adam-p/markdown-here/wiki/Markdown-Cheatsheet#wiki-code).

## Contributing code

- Make sure you have a [GitHub Account](https://github.com/signup/free)
- Fork [dompdf](https://github.com/dompdf/dompdf/)
  ([how to fork a repo](https://help.github.com/articles/fork-a-repo))
- Make your changes
- Add a simple test file in `www/test/`, with a comprehensive name.
- Submit a pull request
([how to create a pull request](https://help.github.com/articles/fork-a-repo))

### Coding standards

- 2 spaces per indentation level, no tabs.
- spaces inside `if` like this:
```php
if ( $foo == "bar" ) {
  //
}
```
- booleans in lowercase
- opening braces *always* on the same line