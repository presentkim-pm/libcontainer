<!-- PROJECT BADGES -->
<div align="center">

[![Packagist][packagist-badge]][packagist-url]
[![CI][ci-badge]][ci-url]
[![Stars][stars-badge]][stars-url]
[![License][license-badge]][license-url]

</div>


<!-- PROJECT LOGO -->
<br />
<div align="center">
  <img src="https://raw.githubusercontent.com/presentkim-pm/libcontainer/main/assets/icon.png" alt="Logo" width="80" height="80"/>
  <h3>libcontainer</h3>
  <p align="center">
    Virtual inventory menus for axolotl-pm/PocketMine-MP: buttons, locked slots and live inventory views, without placing blocks.

[Packagist][packagist-url] · [Changelog](CHANGELOG.md) · [Report a bug][issues-url] · [Request a feature][issues-url]

  </p>
</div>

```php
$menu = Menu::chest("Shop")->setButton(13, VanillaItems::DIAMOND(), fn(Click $c) => $c->player->sendMessage("Bought!"));
$menu->open($player);
```

-----

## 🌟 Why libcontainer?

A menu is an inventory window that is not backed by a real chest. libcontainer shows it as the inventory of an
**invisible entity that only the viewer receives** (an `AddActorPacket` with container metadata), so no block is
placed, no chunk is touched, and nothing is left behind when the player disconnects.

- **No blocks, no world changes** : nothing to restore after a crash
- **Safe by default** : every slot is locked until you unlock it, so an unfinished menu cannot leak items
- **Buttons, free slots and views** : one slot rule model for shops, selectors, dustbins and inventory viewers
- **Chest and hopper** : 27 cells and 5 cells with the vanilla screens; 54 and 243 slots need a resource-pack screen
- **Testable** : the click and slot decisions are plain classes with unit tests that need no server

-----

## 📦 Installation

```sh
composer require presentkim/libcontainer
```

| Requirement | Version                                                          |
|-------------|------------------------------------------------------------------|
| Server      | [axolotl-pm/PocketMine-MP][axolotl-url] 5.51 or newer (fork of PocketMine-MP 5) |
| PHP         | **8.4** or newer                                                 |

> Using it in a plugin: plugins built from [presentkim-pm/plugin-template][plugin-template-url] bundle Composer
> libraries into the phar with shaded namespaces at release, so `composer require` is all a plugin needs.

Register once, usually in `onEnable()`:

```php
use kim\present\libcontainer\LibContainer;

protected function onEnable() : void{
    LibContainer::register($this);
}
```

### Measured behaviour

Measured on Windows Bedrock 1.26.65 with an invisible entity opened through `ContainerOpenPacket::entityInv`.

| Topic                | Result                                                                                                                                  |
|----------------------|-----------------------------------------------------------------------------------------------------------------------------------------|
| Entity types         | Works with `chest_minecart`, `chest_boat`, `hopper_minecart`, `npc`, `armor_stand` and `pig`. The pig shows a Molang error, avoid it. Default stays `minecraft:chest_minecart` (scale 0.01, invisible) |
| `CONTAINER` window   | Vanilla small chest screen, **always 27 cells**, whatever the size is. Sizes 54 and 243 show only the first 27 cells; there is never a large-chest screen |
| `HOPPER` window      | Vanilla hopper screen, 5 cells (size must be 5 or less)                                                                                   |
| Dispenser / dropper  | The client ignores the open and never sends a close. **Rejected** by `ContainerType` (see `SUPPORTED_WINDOW_TYPES`)                      |
| Sizes above 27       | Need a resource pack (JSON UI) screen that draws the extra cells, selected with `->screenKey()`. Measured: the client fills `container_items` up to `CONTAINER_BASE_SIZE` (54 and 243 tested), draws those slots in a JSON UI screen and sends transactions with the real slot number (e.g. slot 147). Buttons mapped to `button.container_auto_place` arrive as a quick move. Without a screen key nothing is logged |
| Distance             | The client closes the window when the entity is about 5 blocks or more from the eyes (5.0 kept, 5.5 closed; vertically +4.8 kept, +5.6 closed). Defaults are 1.5 ahead and 1.0 below; `EntityContainerDisplay` throws when the offset length is 5.0 or more |
| Chat / pause screen  | A window sent while the chat or pause screen is open is **rejected by the client** (it sends a close with window id 255 / -1 at once). libcontainer retries after 5, 10, 20, 40 ticks (`LibContainer::setOpenRetry($attempts, ...$delays)`), then runs the `onClose` handlers once. Opening from the HUD works the first time |
| Clicks beyond size   | The client sends nothing for clicks or drags on cells beyond the server's container size                                                |

Click shapes seen on the wire (all cancelled correctly, no duplication) and the `ClickType` they give:

| Action                      | Transaction                                                          | `ClickType`  |
|-----------------------------|----------------------------------------------------------------------|--------------|
| Tap                         | menu slot item to air, cursor air to item                            | `PICKUP`     |
| Shift + click               | menu slot item to air, player inventory slot air to item (no cursor) | `QUICK_MOVE` |
| Right click on a stack      | menu slot x4 to x2, cursor air to x2                                 | `PICKUP`     |
| Q (drop)                    | drop action, menu slot x5 to x4                                      | `DROP`       |
| Cursor stack onto an item   | cursor Stone x64 to paper x6, menu slot paper x6 to Stone x64        | `SWAP`       |

`SWAP` is only reported when the cursor held an item before the click; a partial take with an empty cursor is `PICKUP`.

> **Stuck windows.** If the client never acknowledges a close (for example after opening a window type the client
> ignores), PocketMine's `InventoryManager` waits for that acknowledgement forever and no later window opens for the
> player until they reconnect. libcontainer cannot repair that from outside, so it only accepts window types known to
> work. If you build your own `ContainerDisplay`, do not open window types you have not measured.

-----

## 🚀 Examples

<details open>
<summary><b>Button menu</b></summary>

```php
use kim\present\libcontainer\Click;
use kim\present\libcontainer\Menu;

$menu = Menu::chest("Server selector");
$menu->setButton(11, VanillaItems::COMPASS()->setCustomName("Survival"), function(Click $click) : void{
    $click->menu->closeThen($click->player, fn() => $this->teleportToSurvival($click->player));
});
$menu->setButton(15, VanillaItems::BED()->setCustomName("Lobby"), fn(Click $click) => $this->sendToLobby($click->player));
$menu->open($player);
```

A click on a button is always cancelled, so the item never moves. The callback runs on the **next tick**, never inside the
transaction event, so closing the menu or opening another one is safe. `$click->clickType` tells a normal click
(`PICKUP`) from shift-click (`QUICK_MOVE`), drop (`DROP`) and replacing the cursor item (`SWAP`).

</details>

<details>
<summary><b>Free slots: a dustbin</b></summary>

```php
$bin = Menu::chest("Dustbin")->unlockAll();
$bin->onClose(fn(Player $player, Menu $menu) => $menu->clear()); // everything put in is gone

// only allow some items, in a single slot
$slot = Menu::chest("Offering")->accept(13, fn(Item $item) : bool => $item->equals(VanillaItems::GOLD_INGOT(), true, false));

// veto or log individual changes
$slot->onChange(function(SlotChange $change) : bool{
    return $change->after->getCount() <= 16; // false cancels the change
});
```

`lock(int ...$slots)` and `unlock(int ...$slots)` change single slots; `lockAll()` is the default.

</details>

<details>
<summary><b>Bound inventory: an inventory viewer</b></summary>

```php
$menu = Menu::large($target->getName() . "'s inventory");
$menu->bind($target->getInventory());              // slots 0..35, two-way and live
$menu->bind($target->getArmorInventory(), 45);     // slots 45..48
$menu->open($admin);

// stop mirroring when you are done
$menu->onClose(fn() => $menu->unbind($target->getInventory()));
```

Changes the admin makes are written to the target's inventory, and changes to the target's inventory (picking up an
item, for example) show up in the menu. Bound slots are free slots.

`bind($source, $offset, $sourceStart, $count, $readOnly)` mirrors only the source slots
`[sourceStart, sourceStart + count)` into the menu slots `[offset, offset + count)`. Ranges may not overlap each other
in the menu, and the same source may be bound again as long as its source slots do not overlap.

</details>

<details>
<summary><b>Paging a 36-slot inventory through 18 cells, and a read-only cursor view</b></summary>

```php
$inv = $target->getInventory();            // 36 slots
$menu = Menu::chest("Inventory");           // 27 slots: 18 cells (0..17), buttons below
$page = 0;

$show = function(int $to) use ($menu, $inv, &$page, &$show) : void{
    $menu->batch(function(Menu $menu) use ($inv, $to, &$page, &$show) : void{
        $menu->unbind($inv);                                  // drop the old window
        $page = $to;
        $menu->bind($inv, 0, $page * 18, 18);                 // slots 0..17 show source slots page*18 ..
        $menu->setButton(21, $prevItem, fn() => $show(0));
        $menu->setButton(23, $nextItem, fn() => $show(1));
    });
};
$show(0);

// a read-only view of the cursor item: locked, only source -> menu
$menu->bind($target->getCursorInventory(), 26, 0, 1, true);
```

`batch()` keeps the re-layout from being written back to the inventory: inside it, menu-side changes (items, buttons,
locks, `bind()`, `unbind()`) are not forwarded to bound sources, and when the outermost batch ends every touched range
is shown from its source once. A menu-side change to a bound slot inside a batch therefore does not last; change the
source instead. `unbind($source)` removes every binding of that source, `unbind($source, $offset)` only the one whose
first menu slot is `$offset`. `setButton()` on a bound slot keeps the button item out of the source, and
`removeButton()` shows the source item there again.

Binding a player's own inventory into a menu they view is safe: a transaction that touches a bound menu slot and the
slot it mirrors (for example shift-clicking hotbar slot 2 into mirrored slot 2) is cancelled instead of applied twice.
Moving between two different mirrored slots works as usual.

</details>

<details>
<summary><b>Custom container types</b></summary>

```php
use kim\present\libcontainer\ContainerType;
use kim\present\libcontainer\ContainerTypes;
use pocketmine\network\mcpe\protocol\types\inventory\WindowTypes;

ContainerTypes::register("myplugin:picker", ContainerType::builder()
    ->size(3)
    ->windowType(WindowTypes::HOPPER) // only CONTAINER and HOPPER are accepted
    ->build());

$menu = Menu::create("myplugin:picker", "Pick one");
```

Built-in ids: `ContainerTypes::CHEST` (27 slots), `HOPPER` (5, `Menu::hopper()`), `LARGE` (54) and `FULL` (243). `LARGE`
and `FULL` show only their first 27 cells unless a resource-pack screen (`->screenKey()`) draws the rest.
Registering an id twice throws. Window types other than `WindowTypes::CONTAINER` and `WindowTypes::HOPPER` throw
`InvalidArgumentException`.
For a different way of showing the window, implement `ContainerDisplay` and pass it with `->display()`.

</details>

<details>
<summary><b>Resource-pack screens</b></summary>

```php
ContainerTypes::register("myplugin:shop", ContainerType::builder()
    ->size(54)
    ->screenKey("§b§w§s§h") // prepended to the title the client receives
    ->build());
```

The client receives `screenKey + title` as the window title, so a resource pack's JSON UI can recognise the prefix
and draw its own screen. Your plugin only sees the plain title.

</details>

<details>
<summary><b>InvUI screens (resource pack)</b></summary>

With a resource pack, a menu can have more than the 27 vanilla cells, buttons that look like anything and read-only
cells that show text and numbers. The window title carries a key that the pack's JSON UI recognises. Design notes and
a sample pack: [Entity inventory UI](https://presentkim.github.io/bedrock-research/design/invui/entity-inventory-ui).

```php
use kim\present\libcontainer\ui\Screen;
use kim\present\libcontainer\ui\ScreenMenu;
use kim\present\libcontainer\ui\Screens;

// once, in onEnable(): the key must be unique, a second registration of it throws
Screens::register(new Screen("§b§w§r§s", 54));

$menu = ScreenMenu::create("§b§w§r§s", "Shop");
$menu->button(10, "Buy sword", fn(Click $c) => $this->buy($c->player), VanillaItems::DIAMOND_SWORD());
$menu->data(0, "Coins", ["Your balance"], count: 12);
$menu->data(1, "1250");                              // numeric names get a "§r" prefix automatically
$menu->data(2, "Health", durability: [15, 20]);
$menu->open($player);

$menu->data(1, "1300");                              // live update, same as setData(1, new DataSlot("1300"))
```

- `Screen(key, size, strictPrefix = true)`: the key is only `§x` pairs and starts with `§b§w` (the wiki convention,
  then a suffix per screen, e.g. `§b§w§r§s`); `Screen::builder()->key(...)->anyKey()->size(...)->build()` allows any
  other key. Size is 1 to 243.
- `Screens::register()` returns the `ContainerType` (window `CONTAINER`, that size, the key as screen key) and throws on
  a key that is already taken, which is how two plugins using the same key are caught.
- `ScreenMenu` wraps a `Menu` (`getMenu()`), so `onClose()`, `setTitle()` and the rest work as usual.

How the pack pairs with it:

| Server                      | Pack (JSON UI)                                                                                           |
|-----------------------------|----------------------------------------------------------------------------------------------------------|
| Title `key . title`         | The screen is selected by the window title (the nametag text, which starts with the key)                |
| Slot `n`                    | `collection_index = n` of the container items collection                                                  |
| `button()`                  | A button mapped to `button.container_auto_place`: a tap arrives as a quick move from slot `n` to the player inventory (`ClickType::QUICK_MOVE`), cancelled like any button click |
| `data()` item name and lore | `#hover_text` (the name, then the lore lines, colour codes included)                                      |
| `data()` count              | `#inventory_stack_count` (1 to 64)                                                                        |
| `data()` durability         | `#item_durability_current_amount` and `#item_durability_total_amount`                                     |

Data cells are locked and carry their values in an item: a paper, or a **diamond pickaxe** when a durability is given.
The client reads the total from the item type, so it is always the pickaxe's 1561 and the current value is scaled
(`[15, 20]` shows 1171 of 1561): use the ratio. A durable item cannot be stacked, so `count` and `durability` exclude
each other. A label does not draw text that is only a number, which is why `Text::label()` puts `§r` in front of it.

Vanilla vs pack: **without** the pack the client opens the vanilla 27-cell chest screen, which draws items and not the
data cells. **With** the pack's screen every slot up to the size is kept and sent with its real slot number
(54 and 243 were measured on Windows 1.26.65).

</details>

<details>
<summary><b>Layout helper</b></summary>

```php
use kim\present\libcontainer\Layout;

Layout::slot(2, 4);                      // 22 (row, column; width 9 by default)
Layout::row(0);                          // [0..8]
Layout::column(8, 6);                    // the last column of a 6-row menu
Layout::rect(1, 1, 2, 7);                // a block of cells
Layout::range(10, 16);
Layout::cell(22);                        // [2, 4]

$menu = Menu::large("Shop");
Layout::border($menu, VanillaBlocks::GLASS_PANE()->asItem()->setCustomName(" "));
Layout::fill($menu, $item, Layout::rect(1, 1, 4, 7));
$inner = Layout::innerSlots(6);          // the cells inside the border
```

Everything but `border()` and `fill()` is pure arithmetic.

</details>

<details>
<summary><b>Paged menu</b></summary>

```php
use kim\present\libcontainer\Layout;
use kim\present\libcontainer\PagedMenu;
use kim\present\libcontainer\PageEntry;

$paged = new PagedMenu(Menu::large("Players"), Layout::rect(0, 0, 5, 9));
$paged->setNavigation(45, VanillaItems::ARROW()->setCustomName("Previous"), 53, VanillaItems::ARROW()->setCustomName("Next"));
$paged->setIndicator(49, fn(int $page, int $pages) => VanillaItems::PAPER()->setCustomName("$page / $pages"));
$paged->setEntries(array_map(
    fn(Player $p) => new PageEntry(VanillaItems::PAPER()->setCustomName($p->getName()), fn(Click $c) => $this->inspect($c->player, $p)),
    $players,
));
$paged->open($admin);
```

Entries with a callback are buttons, the others are locked items. Previous / next are cleared where there is no such
page. `setEntries()` keeps the page (clamped), `setPage()` / `getPage()` count from 0. `$paged->getMenu()` is the plain menu.

</details>

<details>
<summary><b>Await</b></summary>

Needs `composer require sof3/await-generator` (`^3`); libcontainer only loads these classes when you call them.

```php
use kim\present\libcontainer\await\AwaitMenu;
use SOFe\AwaitGenerator\Await;

Await::f2c(function() use ($menu, $player) : \Generator{
    $menu->open($player);
    $slot = yield from AwaitMenu::choose($menu, $player);      // int slot, or null when closed
    $click = yield from AwaitMenu::nextClick($menu, $player);  // Click, or null when closed
    yield from AwaitMenu::closeAndWait($menu, $player);        // closeThen(), so the next open is safe
    $other->open($player);
    yield from AwaitMenu::closed($other, $player);             // resolves when the player closes it
});
```

They wait for the next click on a **button** or the close of that player, whichever comes first; call them after
`open()`. Under the hood they use `Menu::listenClick()` and `Menu::listenClose()`, which return a closure that removes
the listener.

</details>

### Clean tooltips

Measured on Bedrock 1.26.65: a NUL byte (`"\0"`, `chr(0)`) at the end of an item's custom name makes the client show **only
the name** in the tooltip. Vanilla extra lines (attack damage, enchantments, potion effects, category) disappear, the name is
not italic, nothing odd is visible and the hotbar name is fine. In a resource-pack screen `#hover_text` holds only the name.
A literal backslash and zero does nothing. The catch: the client cuts the item's **own lore** after the NUL, so a plain item
shows no lore.

```php
$name = Text::plain("Shop");          // "Shop\0", adding it twice is the same as once
Text::isPlain($name);                 // true
Text::stripPlain($name);              // "Shop"
$icon = Text::plainItem($sword);      // a clone; the custom name (or vanilla name) gets the NUL

$menu->plainButtons();                // setButton() items become plain (off by default, keeps 0.1 behaviour)
$screen->button(10, "Buy", $onClick); // plain by default; pass plain: false to keep the tooltip lines
```

A `DataSlot` with a name only is plain, so any item can be a data cell. A `DataSlot` with lore is never plain.

-----

## 📖 API reference

All classes are in `kim\present\libcontainer`.

| Class / method                                           | Description                                                                 |
|----------------------------------------------------------|-----------------------------------------------------------------------------|
| `LibContainer::register(PluginBase $plugin)`             | Register the listener and hook every player. Idempotent                      |
| `LibContainer::isRegistered()`                           | Whether a registering plugin is enabled                                      |
| `Menu::create(string\|ContainerType $type, string $title = "")` | Create a menu; `chest()`, `hopper()`, `large()`, `full()` are shortcuts                  |
| `getInventory()`, `getType()`, `getTitle()`, `getViewers()` | Read state                                                                |
| `setTitle(string)`                                       | Rename; viewers get the window re-opened                                     |
| `setItem()`, `getItem()`, `setContents()`, `clear()`     | Contents                                                                     |
| `setButton(int $slot, Item $item, \Closure(Click) : void)` | Put an item in the slot and make it a button                                |
| `plainButtons(bool $on = true)`                          | `setButton()` items get a name-only tooltip (see Clean tooltips, 0.2.0)       |
| `removeButton(int $slot)`                                | Remove the callback (the item stays)                                         |
| `lockAll()`, `unlockAll()`, `lock(int ...)`, `unlock(int ...)` | Slot rules, all locked by default                                      |
| `accept(int $slot, \Closure(Item) : bool)`               | Free slot that only accepts validated items                                  |
| `onChange(\Closure(SlotChange) : bool)`                  | Veto changes to free slots                                                   |
| `onOpen(\Closure(Player))`, `onClose(\Closure(Player, Menu))` | Open and close handlers                                                |
| `bind(Inventory $source, int $offset = 0, int $sourceStart = 0, ?int $count = null, bool $readOnly = false)`, `unbind(Inventory $source, ?int $offset = null)` | Mirror a slot range of an inventory (two-way, or source to menu only) |
| `batch(\Closure(Menu) : void)`                            | Re-lay out the menu without writing back to bound sources (0.2.0)            |
| `open(Player) : bool`, `close(Player)`                   | Show and hide                                                                |
| `closeThen(Player, \Closure)`                            | Close, then run after the client acknowledged                                |
| `listenClick(\Closure(Click))`, `listenClose(\Closure(Player))` | Removable listeners; return a closure that stops listening (0.2.0)          |
| `Screen`, `Screens::register()`, `ScreenMenu`, `DataSlot` (`ui`) | InvUI screens for resource packs (0.2.0)                      |
| `Layout`, `PagedMenu`, `PageEntry`                       | Grid slot helpers and paging (0.2.0)                                         |
| `AwaitMenu` (`await`)                                    | `nextClick()`, `choose()`, `closed()`, `closeAndWait()` generators (0.2.0)   |
| `ContainerType::builder()`                               | `size()`, `windowType()`, `entityType()`, `screenKey()`, `display()`, `build()` |
| `ContainerTypes::register()`, `get()`, `has()`, `all()`  | Registry of named types                                                      |

### How a click is handled

One listener watches `InventoryTransactionEvent` (priority `HIGH`) and looks at the menu slots the transaction touches:

1. Any **button**: the event is cancelled and each touched button's callback runs once, on the next tick.
2. Else any **locked** slot: the event is cancelled.
3. Else (**free** slots only): validators from `accept()` run, then the `onChange()` handlers. A `false` cancels the event.

Windows are cleaned up when they close, when the player quits, dies or teleports. `onOpen` / `onClose` are not called again when `setTitle()` re-opens the window.

-----

## 🧪 Development

```sh
composer install --ignore-platform-req='ext-*'
composer test
```

The server's native extensions are not needed for the tests. The decisions about clicks live in
`kim\present\libcontainer\logic` as plain classes without server types, which is why they are covered by unit tests,
while opening menus needs a real server and is not.

-----

## 📄 License

Distributed under the **MIT** License. See [LICENSE][license-url] for more information.


[packagist-badge]: https://img.shields.io/packagist/v/presentkim/libcontainer.svg?style=for-the-badge

[packagist-url]: https://packagist.org/packages/presentkim/libcontainer

[ci-badge]: https://img.shields.io/github/actions/workflow/status/presentkim-pm/libcontainer/ci.yml?branch=main&style=for-the-badge

[ci-url]: https://github.com/presentkim-pm/libcontainer/actions/workflows/ci.yml

[stars-badge]: https://img.shields.io/github/stars/presentkim-pm/libcontainer.svg?style=for-the-badge

[license-badge]: https://img.shields.io/github/license/presentkim-pm/libcontainer.svg?style=for-the-badge

[stars-url]: https://github.com/presentkim-pm/libcontainer/stargazers

[issues-url]: https://github.com/presentkim-pm/libcontainer/issues

[license-url]: https://github.com/presentkim-pm/libcontainer/blob/main/LICENSE

[axolotl-url]: https://github.com/axolotl-pm/PocketMine-MP

[plugin-template-url]: https://github.com/presentkim-pm/plugin-template
