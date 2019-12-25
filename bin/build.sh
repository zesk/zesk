#!/bin/bash
export APPLICATION_ROOT="$(cd $(dirname "$BASH_SOURCE")/..; pwd)"
ERR_ENV=1
ERR_BUILD=93
ERR_TAR=15
start=$(date +%s)
COMPOSER=$(which composer)

if [ -z "$COMPOSER" ]; then
	echo "No composer in PATH=$PATH" 1>&2
	exit $ERR_ENV
fi

cd $APPLICATION_ROOT

if [ "$(git status -s)" != "" ]; then
	echo "FAILED: Git status is not empty" 1>&2
	git status -s 1>&2
	exit $ERR_ENV
fi

backup_composer=composer.json.$$.BACK

cp composer.json "$backup_composer"
zesk module iless update
composer install --dev --no-interaction --quiet

for d in \
	./modules/content/ \
	./modules/dropfile/share/ \
	./modules/image_picker/ \
	./modules/inplace/share/ \
	./modules/logger_footer/ \
	./modules/markdown/ \
	./modules/modal_url/ \
	./modules/picker/ \
	./modules/polyglot/share/ \
	./modules/server/ \
	./modules/workflow/share/ \
	./share/less; do
	echo "Building $d"
	if ! zesk module iless lessc --cd "$d" --mkdir-target; then
		exit $ERR_BUILD
	fi
done

mv -f $backup_composer composer.json
rm -rf vendor

composer install --dev --no-interaction --quiet

if [ "$(git status -s)" != "" ]; then
	echo "FAILED: Git status FAILED second round" 1>&2
	git status -s 1>&2
	exit $ERR_ENV
fi
