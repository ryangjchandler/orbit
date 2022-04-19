---
title: Custom 404 Page
description: Custom 404 pages with Jigsaw docs starter template
extends: _layouts.documentation
section: content
---

# Custom 404 Page {#custom-404-page}

This starter template includes a custom __404 Not Found__ error page, located at `/source/404.blade.php`. [To preview the 404 page](/404), you can visit `/404` in your browser.

```html
<!-- source/404.blade.php -->
@extends('_layouts.master')

@section('body')
<div class="flex flex-col items-center mt-32 text-gray-700">
    <h1 class="text-6xl leading-none mb-2">404</h1>
    <h2 class="text-3xl">Page not found</h2>

    <hr class="block w-full max-w-lg mx-auto my-8 border">

    <p class="text-xl">Need to update this page? See the <a title="404 Page Documentation" href="/docs/404"> documentation here</a>.</p>
</div>
@endsection
```

---

Depending on where your site is hosted, you may need to configure your server to use the custom 404 page. For more details, visit the [Jigsaw documentation about configuring a custom 404 page.](https://jigsaw.tighten.co/docs/custom-404-page/)
