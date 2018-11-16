# Likes Ratings Plugin

Details coming soon...


# CLI Commands

### List (ls)
List the likes-ratings entries. 

Optional Argument is:
1. `id`: The **id** stored in the DB that you want to retrieve.

Options you can pass are:
1. `--limit`: Default, **10** 
2. `--sort`: Can be either **desc** or **asc**. Default, **desc**.
3. `--by`: Can be either **ups** or **downs**. Default, **ups**.

#### Returns only the entry for `foobar`
```
bin/plugin likes-ratings ls foobar
```

#### Returns a full list
```
bin/plugin likes-ratings ls
```

#### Returns a list of 3 entries, sorted by ASC and UPS
```
bin/plugin likes-ratings ls --limit 3 --sort asc --by ups
```

### Set (set)
Sets an amount of ups or downs count for a specified ID entry. 

Required Arguments are:
1. `id`: The **id** stored in the DB that you want to manipulate.
2. `count`: The amount that needs to be changed. Must be a number. If prefixed with `+` or `-`, arithmetic operations will be performed to the values (see examples below).

> Note that prefixing with the `-` requires for your command to be escaped with `--`, see example below.

Options you can pass are:
1. `--type`: Can be either **ups** or **downs**. Default, **ups**.

#### `foobar` will be set to 50 ups (ups: 50, downs: 0)
```
bin/plugin likes-ratings set foobar 50
```

#### `foobar` will be set to 50 downs (ups: 50, downs: 10)
```
bin/plugin likes-ratings set foobar 10 --type downs
```

#### `foobar` will be set to 55 ups (ups: 55, downs: 10)
```
bin/plugin likes-ratings set foobar +5
```

#### `foobar` will be set to 0 downs (ups: 55, downs: 0)

Because `-10` might be interpreted as an option by the CLI, it must be escaped with a `--` like shown below. 
```
bin/plugin likes-ratings set foobar --type downs -- -10
```
