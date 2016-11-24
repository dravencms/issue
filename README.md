# Dravencms Issue module

This is a simple issue module for dravencms

## Instalation

The best way to install dravencms/issue is using  [Composer](http://getcomposer.org/):


```sh
$ composer require dravencms/issue:@dev
```

Then you have to register extension in `config.neon`.

```yaml
extensions:
	issue: Dravencms\Issue\DI\IssueExtension
```
