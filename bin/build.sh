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
backup_vendor=vendor.$$.BACK

cp composer.json "$backup_composer"
mv vendor "$backup_vendor"

composer install --dev --no-interaction --quiet
zesk module iless update > /dev/null 2>&1
composer install --dev --no-interaction --quiet

finish() {
	rm -rf vendor
	mv "$backup_vendor"	vendor
	mv -f "$backup_composer" composer.json
	if [ ! -z "$1" ]; then
		exit $1
	fi
}

for d in \
	./modules/content/ \
	./modules/dropfile/share/ \
	./modules/image_picker/ \
	./modules/inplace/share/ \
	./modules/logger_footer/ \
	./modules/modal_url/ \
	./modules/picker/ \
	./modules/polyglot/share/ \
	./modules/workflow/share/ \
	; do
	echo "Building $d"
	if ! zesk module iless lessc --cd "$d" --mkdir-target; then
		finish $ERR_BUILD
	fi
done

# for f in \
# 	./modules/server/site/less/server.less \
# 	; do
# 	echo "Building $f"
# 	if ! zesk module iless lessc --mkdir-target "$f"; then
# 		finish $ERR_BUILD
# 	fi
# done

for d in \
	./modules/markdown/ \
	; do
	echo "Building $d"
	if ! zesk module iless lessc --cd "$d" --target-path=./; then
		finish $ERR_BUILD
	fi
done

finish

if [ "$(git status -s)" != "" ]; then
	echo "FAILED: Git status FAILED second round" 1>&2
	git status -s 1>&2
	exit $ERR_ENV
fi
