# Changelog

## Unreleased

### ✨ Features
- InvUI screens (`kim\present\libcontainer\ui`): `Screen` (validated key, size 1..243), `Screens::register()` (returns the
  `ContainerType`, rejects a key registered twice), `ScreenMenu` with `button()`, `data()` / `setData()` and the
  `DataSlot` value object. Data cells are locked items whose name, lore, count and durability a resource-pack screen
  reads through `#hover_text`, `#inventory_stack_count` and `#item_durability_*_amount`. Numeric-only names get a `§r` prefix.
- `Layout`: slot / cell arithmetic for grids (`slot()`, `row()`, `column()`, `rect()`, `range()`, `borderSlots()`), plus `border()` and `fill()`.
- `PagedMenu` and `PageEntry`: paged lists with previous / next buttons, optional page indicator, `setEntries()` and `setPage()`.
- Await support (`kim\present\libcontainer\await\AwaitMenu`): `nextClick()`, `choose()`, `closed()` and `closeAndWait()`
  for `sof3/await-generator` ^3 (suggested; a dev dependency for the tests, never loaded unless used).
- Ranged `Menu::bind($source, $offset, $sourceStart, $count, $readOnly)`: mirror source slots `[sourceStart, sourceStart + count)`
  into menu slots `[offset, offset + count)`. Read-only bindings lock their slots and only copy source to menu.
  `unbind($source, $offset)` removes a single binding (omit `$offset` to remove all of that source), so a pager can rebind per page.
- `Menu::batch()`: menu-side changes inside it (items, buttons, locks, bind/unbind) are not written back to bound sources;
  touched ranges are shown from their source once when the outermost batch ends.
- `setButton()` on a bound slot no longer writes the button item to the source; `removeButton()` shows the source item again.
- `Menu::listenClick()` and `Menu::listenClose()`: listeners that return a closure to remove them.

### 📏 Notes
- A pack screen needs the key in the window title and maps its buttons to `button.container_auto_place`, which arrive as a quick move.
  Without the pack the vanilla 27-cell screen is shown and the data cells are not drawn.
- Durability is carried by a diamond pickaxe, so the total the client reads is always 1561 and the current value is scaled.
- `bind($source, $offset)` of 0.1.0 works unchanged. A source may now be bound more than once if its source ranges do not overlap.

## 0.1.1 (2026-11-27)

### 🐛 Fixed
- `LibContainer::register()` crashed the server: the teleport handler took `PlayerTeleportEvent`, which
  axolotl-pm/PocketMine-MP does not have. It now listens to `EntityTeleportEvent` and closes the menu when the entity
  is a player.
- Added a test that every listener handler takes an event class that exists.

## 0.1.0 (2026-11-27)

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
