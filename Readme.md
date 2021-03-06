# wp-cli proxy

[![Build Status](https://travis-ci.org/pixline/wp-cli-proxy.png)](https://travis-ci.org/pixline/wp-cli-proxy) [![Support](https://www.paypalobjects.com/en_US/i/btn/btn_donate_SM.gif)](https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=CX6VQ6FVJFN4L)

Wrapper around [mitmproxy](http://mitmproxy.org). Install, configure and run a local debug proxy alongside WordPress, allowing interception, analysis and replay of every HTTP(S) request and response. 

## Setup

* Install [wp-cli](http://wp-cli.org)
* Add wp-cli package index:
```
composer config repositories.wp-cli composer http://wp-cli.org/package-index/
```

* Install command:
```
composer require pixline/wp-cli-proxy=dev-master
```
 
## Subcommands

```
config       Add proxy configuration constants to wp-config.php (or dump them to console).
install      Install mitmproxy
start        Start mitmproxy
version      Return wp-cli + mitmproxy versions
```

### Usage

```
wp proxy config [--dump]
wp proxy install
wp proxy start [<port>] [--flags=<flags>]
wp proxy version
```

See `wp proxy <subcommand> --help` (or source code) for details.

### wp-cli.yml example

```
proxy start:
  port: 1234
  flags: --palette=solarized_dark
```

## Changelog

### 0.1.3 (07/12/2013)

* `sudo` install support
* minor refactoring
* 

### 0.1.2

* First public release

## Credits

Copyright (c) 2013+ Paolo Tresso / [SWERgroup](http://swergroup.com)

Plugin released under the [MIT License](http://opensource.org/licenses/MIT)
