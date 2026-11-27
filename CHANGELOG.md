# Changelog

## Unreleased

### ✨ Features
- `LibContainer::register()`: one call hooks the server's container-open callbacks and registers the event listener.
- `Menu`: virtual inventory windows (`Menu::chest()`, `large()`, `full()`, or any `ContainerType`) backed by an
  invisible, packet-only entity container, with a title that can be changed while it is open.
- Buttons (`setButton()`), locked slots (the default), free slots with validators (`accept()`), and
  `onChange()` / `onOpen()` / `onClose()` handlers.
- `bind()`: mirror an existing inventory (a player's inventory, an ender chest, ...) into a slot range of a menu,
  live in both directions.
- `Click` (with an inferred `ClickType`) and `SlotChange` value objects for callbacks.
- `ContainerType` / `ContainerTypes`: built-in `CHEST` (27), `LARGE` (54) and `FULL` (243) sizes, custom types with
  their own window type, entity type, resource-pack screen key and `ContainerDisplay`.
- `closeThen()`: run code after the client acknowledged the close, e.g. to open the next menu.

### ⚠️ Known limitations
- The default entity type (`ContainerType::DEFAULT_ENTITY_TYPE`) and the entity's placement are provisional until
  measured in game.
