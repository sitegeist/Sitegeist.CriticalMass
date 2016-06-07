# Sitegeist.CriticalMass

## Authors & Sponsors

* Martin Ficzel - ficzel@sitegeist.de

*The development and the public-releases of this package is generously sponsored 
by our employer http://www.sitegeist.de.*

## Installation 

For now this package is not listed at packagist.org, so it needs to be configured via the `repositories` option in your composer.json.

```json
{
  "repositories": [
    {
      "url": "ssh://git@git.sitegeist.de:40022/sitegeist/Sitegeist.CriticalMass.git",
      "type": "vcs"
    }
  ]
}
```

You can then require it as a regular dependency:

```json
{
  "dependencies": {
    "sitegeist/criticalmass": "@dev"
  }
}
```

Currently `@dev` is recommended, since this package is still under development. Later on it should be replaced by the according semver string.

After you finished configuring your composer.json, run the following command to retrieve the package:

```shell
composer update sitegeist/criticalmass