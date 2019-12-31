# Twig Integration for Mezzio

[![Build Status](https://travis-ci.org/mezzio/mezzio-twigrenderer.svg?branch=master)](https://travis-ci.org/mezzio/mezzio-twigrenderer)

Provides [Twig](http://twig.sensiolabs.org/) integration for
[Mezzio](https://github.com/mezzio/mezzio).

## Installation

Install this library using composer:

```bash
$ composer require mezzio/mezzio-twigrenderer
```
We recommend using a dependency injection container, and typehint against
[container-interop](https://github.com/container-interop/container-interop). We
can recommend the following implementations:

- [laminas-servicemanager](https://github.com/laminas/laminas-servicemanager):
  `composer require laminas/laminas-servicemanager`
- [pimple-interop](https://github.com/moufmouf/pimple-interop):
  `composer require mouf/pimple-interop`
- [Aura.Di](https://github.com/auraphp/Aura.Di)

## Documentation

See the [mezzio](https://github.com/mezzio/mezzio/blob/master/doc/book)
documentation tree, or browse online at http://mezzio.rtfd.org.
