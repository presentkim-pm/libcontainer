#!/usr/bin/env python3
"""Rename the template placeholders for a new library.

Usage (from the repository root):
    python3 .github/scripts/setup-template.py <owner/repo> [description]
"""
import datetime
import json
import os
import re
import shutil
import sys

TEMPLATE_REPO = "presentkim-pm/pmmp-library-template"
DEFAULT_DESCRIPTION = "A library for axolotl-pm/PocketMine-MP"

RESERVED = set("""
abstract and array as break callable case catch class clone const continue declare default do echo else elseif
empty enddeclare endfor endforeach endif endswitch endwhile enum eval exit extends final finally fn for foreach
function global goto if implements include instanceof insteadof interface isset list match namespace new or print
private protected public readonly require return static switch throw trait try unset use var while xor yield
int float bool string true false null void iterable object mixed never self parent
""".split())

SKIP_DIRS = {".git", "vendor"}
SKIP_FILES = {
    os.path.normpath(".github/scripts/setup-template.py"),
    os.path.normpath(".github/workflows/setup.yml"),
}


def fail(message):
    print("error: " + message, file=sys.stderr)
    sys.exit(1)


def main():
    if len(sys.argv) < 2 or "/" not in sys.argv[1]:
        fail("usage: setup-template.py <owner/repo> [description]")
    full_repo = sys.argv[1].strip()
    owner, _, repo = full_repo.partition("/")
    if not owner or not repo or "/" in repo:
        fail("invalid repository '" + full_repo + "', expected <owner>/<repo>")

    description = sys.argv[2].strip() if len(sys.argv) > 2 else ""
    if not description:
        description = DEFAULT_DESCRIPTION

    name = repo.lower()
    compact = re.sub(r"[^a-z0-9]", "", name)
    pascal = "".join(p[:1].upper() + p[1:] for p in re.split(r"[^A-Za-z0-9]+", repo) if p)

    if not compact or not pascal:
        fail("repository name '" + repo + "' has no letters or digits to build a namespace from")
    if compact[0].isdigit() or pascal[0].isdigit():
        fail("repository name '" + repo + "' must not start with a digit (it becomes a PHP namespace and class name)")
    if compact in RESERVED:
        fail("repository name '" + repo + "' becomes the reserved PHP word '" + compact + "', choose another name")

    try:
        with open("composer.json", encoding="utf-8") as f:
            composer = f.read()
    except OSError:
        fail("composer.json not found, run this script from the repository root")
    if "presentkim/library-name" not in composer:
        print("already set up, nothing to do")
        return 0

    # 1. Move the template docs into place
    for doc in ("README.md", "CHANGELOG.md"):
        shutil.move(os.path.join(".github", "template", doc), doc)
    shutil.rmtree(os.path.join(".github", "template"), ignore_errors=True)

    # 2. Replace placeholders in text files
    json_description = json.dumps(description)[1:-1]
    for root, dirs, files in os.walk("."):
        dirs[:] = [d for d in dirs if d not in SKIP_DIRS]
        for file in files:
            path = os.path.normpath(os.path.join(root, file))
            if path in SKIP_FILES:
                continue
            with open(path, "rb") as f:
                raw = f.read()
            if b"\0" in raw:
                continue
            try:
                text = raw.decode("utf-8")
            except UnicodeDecodeError:
                continue
            desc = json_description if path.endswith(".json") else description
            new = text.replace(TEMPLATE_REPO, full_repo)
            new = new.replace("Description of Library", desc)
            new = new.replace("library-name", name)
            new = new.replace("LibraryName", pascal)
            new = new.replace("libraryname", compact)
            if new != text:
                with open(path, "wb") as f:
                    f.write(new.encode("utf-8"))

    # 3. Rename directories and files
    base = os.path.join("src", "kim", "present")
    os.rename(os.path.join(base, "libraryname"), os.path.join(base, compact))
    os.rename(os.path.join(base, compact, "LibraryName.php"), os.path.join(base, compact, pascal + ".php"))
    os.rename(os.path.join("tests", "LibraryNameTest.php"), os.path.join("tests", pascal + "Test.php"))

    # 4. License year
    with open("LICENSE", encoding="utf-8") as f:
        license_text = f.read()
    year = str(datetime.datetime.now(datetime.timezone.utc).year)
    license_text = re.sub(r"Copyright \(c\) \d{4} PresentKim", "Copyright (c) " + year + " PresentKim", license_text)
    with open("LICENSE", "w", encoding="utf-8", newline="") as f:
        f.write(license_text)

    # 5. Remove the setup files
    for path in (".github/workflows/setup.yml", ".github/scripts/setup-template.py"):
        if os.path.exists(path):
            os.remove(path)
    scripts = os.path.join(".github", "scripts")
    if os.path.isdir(scripts) and not os.listdir(scripts):
        os.rmdir(scripts)

    print("Package   : presentkim/" + name)
    print("Namespace : kim\\present\\" + compact)
    print("Class     : " + pascal)
    return 0


if __name__ == "__main__":
    sys.exit(main())
