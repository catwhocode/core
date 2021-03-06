---
currentMenu: blocks
---
# How to implement a block

From a developer's point of view, a block needs to be seen as two classes working together to provide what the
user sees. First there is the `BlockEntity` class (part of the BlocksModule). The BlockEntity class defines the title, 
filters, including language and placement, and most importantly, an array of block *properties*. The block properties
are defined by a second class called a *BlockHandler*. (In previous versions of the core, this was referred to as the
"block class"). This class is provided by a module and can define any number of properties. It is the Handler's
responsibility to display and modify the properties using a Symfony Form class.

## Requirements

- BlockHandler class names should be suffixed by `Block` and located in the `ModuleRoot/Block/` directory.
- BlockHandler classes must implement `Zikula\BlocksModule\BlockHandlerInterface`.
- `Zikula\BlocksModule\AbstractBlockHandler` is available if desired.
- BlockHandlers must register their permission schema as part of the owning module's array (in `composer.json`).
- BlockHandler must define a Symfony FormType class to allow editing of the block properties if this is needed.
  Otherwise the `getFormClassName` method must return `null`.
  - A simple Twig template is available as a default `@ZikulaBlocksModule/Block/default_modify.html.twig`.
    - if you implement your own modify template, **do not** render the `form_start(form)` or `form_end(form)`
      tags within your template.
- The `$properties` parameter in the `display` method is an array from BlockEntity.

### Differences from Core 1.x Block classes 

- The old "info" array of the block has been eliminated.
  - `module` is inferred from providing module.
  - `text_type` is replaced by `getType()`.
  - `allow_multiple` is always `true`.
  - `form_content` is no longer allowed (blocks MUST implement their own content control).
- The `init` and `modify` methods have been eliminated.
