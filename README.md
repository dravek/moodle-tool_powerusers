# Power users generator [![Build Status](https://github.com/dravek/moodle-tool_powerusers/workflows/moodle-plugin-ci/badge.svg)](https://github.com/dravek/moodle-tool_powerusers/actions)

This generator uses the SuperHero API to generate users from your favorite characters in your Moodle instance.

To generate a user just type the name and press the button. The user will be automatically created in your Moodle instance. This user will be created with the name, profile picture and biography details.
By default the password for all generated users will be generated randomly, but you have the option to add your own password manually.

Generate your API token at [https://superheroapi.com](https://superheroapi.com) and add it in plugin settings.

## Migration note

The old `marvelapi` class is deprecated. New development should use `\tool_powerusers\superheroapi`.

## Upgrade steps

1. Go to Site administration > Plugins > Admin tools > Power users settings.
2. Add your SuperHero API token.
3. Existing Marvel public/private key settings are no longer used.

