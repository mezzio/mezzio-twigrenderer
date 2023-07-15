# Twig Integration for Mezzio

[![Build Status](https://github.com/mezzio/mezzio-twigrenderer/actions/workflows/continuous-integration.yml/badge.svg)](https://github.com/mezzio/mezzio-twigrenderer/actions/workflows/continuous-integration.yml)

> ## 🇷🇺 Русским гражданам
>
> Мы, участники Laminas, родились и живем в разных странах. У многих из нас есть друзья, родственники и коллеги как в России, так и в Украине. Некоторые из нас родились в России. Некоторые из нас живут в России. У некоторых бабушки и дедушки сражались с фашистами во Второй мировой войне. Здесь никто не поддерживает фашизм.
>
> У одного из нас есть украинская родственница, которая спаслась из дома вместе с сыном. Поезд задержался из-за бомбежки на дороге впереди. У нас есть друзья, которые прячутся в бомбоубежищах. Мы с тревогой ждем весточки от них после воздушных налетов, которые беспорядочно наносят удары и попадают по больницам, школам, детским садам и домам. Мы не берем это из каких-либо СМИ. Мы наблюдаем это напрямую.
>
> Вы доверяете нам достаточно, чтоб использовать наши программы, и мы просим вас довериться нам вновь. Мы нуждаемся в помощи. Выходите и протестуйте против этой бесполезной войны. Остановите кровопролитие. Скажите "Нет войне!"
>
> ## 🇺🇸 To Citizens of Russia
>
> We at Laminas come from all over the world. Many of us have friends, family and colleagues in both Russia and Ukraine. Some of us were born in Russia. Some of us currently live in Russia. Some have grandparents who fought Nazis in World War II. Nobody here supports fascism.
>
> One team member has a Ukrainian relative who fled her home with her son. The train was delayed due to bombing on the road ahead. We have friends who are hiding in bomb shelters. We anxiously follow up on them after the air raids, which indiscriminately fire at hospitals, schools, kindergartens and houses. We're not taking this from any media. These are our actual experiences.
>
> You trust us enough to use our software. We ask that you trust us to say the truth on this. We need your help. Go out and protest this unnecessary war. Stop the bloodshed. Say "stop the war!"

Provides [Twig](https://twig.symfony.com/) integration for
[Mezzio](https://docs.laminas.dev//mezzio/).

## Installation

Install this library using composer:

```bash
$ composer require mezzio/mezzio-twigrenderer
```

We recommend using a dependency injection container, and typehint against [container-interop](https://github.com/container-interop/container-interop). We can recommend the following implementations:

- [laminas-servicemanager](https://github.com/laminas/laminas-servicemanager):
  `composer require laminas/laminas-servicemanager`
- [pimple-interop](https://github.com/moufmouf/pimple-interop):
  `composer require mouf/pimple-interop`
- [Aura.Di](https://github.com/auraphp/Aura.Di): `composer require aura/di`

## Twig Extension

The included Twig extension adds support for url generation. The extension is automatically activated if the [UrlHelper](https://github.com/mezzio/mezzio-helpers#urlhelper) and [ServerUrlHelper](https://github.com/mezzio/mezzio-helpers#serverurlhelper) are registered with the container.

- ``path``: Render the relative path for a given route and parameters. If there
  is no route, it returns the current path.

  ```twig
  {{ path('article_show', {'id': '3'}) }}
  Generates: /article/3
  ```
  
  ``path`` supports optional query parameters and a fragment identifier.

  ```twig
  {{ path('article_show', {'id': '3'}, {'foo': 'bar'}, 'fragment') }}
  Generates: /article/3?foo=bar#fragment
  ```

  By default the current route result is used where applicable. To disable this
  the `reuse_result_params` option can be set.

  ```twig
  {{ path('article_show', {}, {}, null, {'reuse_result_params': false}) }}
  ```

- ``url``: Render the absolute url for a given route and parameters. If there is
  no route, it returns the current url.

  ```twig
  {{ url('article_show', {'slug': 'article.slug'}) }}
  Generates: http://example.com/article/article.slug
  ```

  ``url`` also supports query parameters and a fragment identifier.

  ```twig
  {{ url('article_show', {'id': '3'}, {'foo': 'bar'}, 'fragment') }}
  Generates: http://example.com/article/3?foo=bar#fragment
  ```

  By default the current route result is used where applicable. To disable this
  the `reuse_result_params` option can be set.

  ```twig
  {{ url('article_show', {}, {}, null, {'reuse_result_params': false}) }}
  ```

- ``absolute_url``: Render the absolute url from a given path. If the path is
  empty, it returns the current url.

  ```twig
  {{ absolute_url('path/to/something') }}
  Generates: http://example.com/path/to/something
  ```

- ``asset`` Render an (optionally versioned) asset url.

  ```twig
  {{ asset('path/to/asset/name.ext', version=3) }}
  Generates: path/to/asset/name.ext?v=3
  ```

  To get the absolute url for an asset:

  ```twig
  {{ absolute_url(asset('path/to/asset/name.ext', version=3)) }}
  Generates: http://example.com/path/to/asset/name.ext?v=3
  ```

## Configuration

If you use the [laminas-component-installer](https://github.com/laminas/laminas-component-installer) the factories are configured automatically for you when requiring this package with composer. Without the component installer, you need to include the [`ConfigProvider`](src/ConfigProvider.php) in your [`config/config.php`](https://github.com/mezzio/mezzio-skeleton/blob/master/config/config.php).
Optional configuration can be stored in `config/autoload/templates.global.php`.

```php
'templates' => [
    'extension' => 'file extension used by templates; defaults to html.twig',
    'paths' => [
        // namespace / path pairs
        //
        // Numeric namespaces imply the default/main namespace. Paths may be
        // strings or arrays of string paths to associate with the namespace.
    ],
],
'twig' => [
    'cache_dir' => 'path to cached templates',
    'assets_url' => 'base URL for assets',
    'assets_version' => 'base version for assets',
    'extensions' => [
        // extension service names or instances
    ],
    'runtime_loaders' => [
        // runtime loaders names or instances   
    ],
    'globals' => [
        // Global variables passed to twig templates
        'ga_tracking' => 'UA-XXXXX-X'
    ],
    'timezone' => 'default timezone identifier, e.g.: America/New_York',
    'optimizations' => -1, // -1: Enable all (default), 0: disable optimizations
    'autoescape' => 'html', // Auto-escaping strategy [html|js|css|url|false]
    'auto_reload' => true, // Recompile the template whenever the source code changes
    'debug' => true, // When set to true, the generated templates have a toString() method
    'strict_variables' => true, // When set to true, twig throws an exception on invalid variables
],
```

## Documentation

See the Mezzio [Twig documentation](https://docs.mezzio.dev/mezzio/features/template/twig/).
