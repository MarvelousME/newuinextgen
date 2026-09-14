# Motion Engine Developer API

## Browser

```js
// Register custom effect (whitelist must also include PHP registry entry)
NGT3D.registerAnimation('my-effect', {
  id: 'my-effect',
  source: 'CUSTOM',
  apply(el, options, ctx) {
    const tween = ctx.gsap.from(el, { autoAlpha: 0, y: 40, duration: 0.8 });
    return () => tween.kill();
  }
});

NGT3D.init();
NGT3D.refresh();
NGT3D.destroy();
NGT3D.getDiagnostics?.() || NGT3D; // debug snapshot via diagnostics when enabled
```

### Plugin detection

```js
const snap = NGT3DPluginManager.snapshot();
// snap.available.SplitText, snap.missing, …
NGT3DPluginManager.requirePlugins(['ScrollTrigger', 'SplitText']);
```

### Transform ownership

```js
NGT3DTransformComposer.claim(el, 'transform', 'effect-a');
NGT3DTransformComposer.ensureLayer(el, 'text');
NGT3DTransformComposer.warningsFor(el);
```

### Primitives

```js
NGT3DMotionPrimitives.applyEntrance(el, options, ctx, { direction: 'up', blur: 8 });
NGT3DMotionPrimitives.applyTextSplit(el, options, ctx, { split: 'chars' });
NGT3DMotionPrimitives.applyMagnetic(el, options, ctx);
```

## WordPress

```php
add_filter( 'ngt_3d_animation_registry', function( $reg ) {
  $reg['my-effect'] = [ /* metadata */ ];
  return $reg;
} );

$counts = NGT3D_Motion_Catalogue::counts();
```

## REST

Existing `ngt3d/v1` endpoints unchanged. Prefer `GET /registry` for source-tagged catalogue.
