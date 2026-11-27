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
- `ContainerType` / `ContainerTypes`: built-in `CHEST` (27), `HOPPER` (5, `Menu::hopper()`), `LARGE` (54) and `FULL` (243), custom types with
  their own window type, entity type, resource-pack screen key and `ContainerDisplay`.
- `closeThen()`: run code after the client acknowledged the close, e.g. to open the next menu.

### 📏 Measured (Windows Bedrock 1.26.65)
- Any entity type opens the window; `minecraft:chest_minecart` stays the default (the pig logs a Molang error).
- `CONTAINER` always shows the vanilla 27-cell screen; `HOPPER` shows 5 cells. `ContainerType` now accepts only these
  two window types (`SUPPORTED_WINDOW_TYPES`) because dispenser and dropper are ignored by the client, which never
  acknowledges a close and leaves the server waiting forever. A hopper type must have 5 cells or fewer.
- `EntityContainerDisplay` throws when the entity offset is 5.0 blocks or more from the eyes, where the client closes the window.
- `ClickTypeResolver` reports `SWAP` only when the cursor held an item, so a partial right click is `PICKUP`.

### ⚠️ Known limitations
- `LARGE` (54) and `FULL` (243) need a resource-pack screen (`screenKey`) to show more than 27 cells; the vanilla
  screen draws a 9x3 grid only. With such a screen the client keeps and sends every slot up to the base size.
