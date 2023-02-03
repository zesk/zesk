#!/bin/bash
ERR_ENV=1
ERR_BUILD=93

top="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." || exit $ERR_ENV; pwd)"
start=$(date +%s)
composer=$(which composer)

if [ -z "$composer" ]; then
	echo "No composer in PATH=$PATH" 1>&2
	exit $ERR_ENV
fi

cd "$top" || exit $ERR_ENV

# if [ "$(git status -s)" != "" ]; then
# 	echo "FAILED: Git status is not empty" 1>&2
# 	git status -s 1>&2
# 	exit $ERR_ENV
# fi

backup_composer=composer.json.$$.BACK
backup_vendor=vendor.$$.BACK

cp composer.json "$backup_composer"
mv vendor "$backup_vendor"

$composer install --dev --no-interaction --quiet

zesk="$top/bin/zesk.sh"
if [ ! -x "$zesk" ]; then
	echo "Not executable: $zesk" 1>&2
	exit $ERR_ENV
fi


$zesk module iless update > /dev/null 2>&1
$composer install --dev --no-interaction --quiet

finish() {
	rm -rf vendor
	mv "$backup_vendor"	vendor
	mv -f "$backup_composer" composer.json
	if [ -n "$1" ]; then
		exit "$1"
	fi
  echo "Completed in $((start - $(date +%s))) seconds"
}


build_less_directory() {
  local lessDir
  lessDir=$1
  echo "Building $lessDir"
  cd "$lessDir/.." || return $ERR_ENV
  lessDir=$(pwd)
  cd "$top" || return $ERR_ENV
  if ! $zesk module iless lessc --cd "$lessDir" --mkdir-target; then
    finish $ERR_BUILD
  fi
}

build_less_directories() {
  while IFS= read -r -d '' lessDir; do
    if ! build_less_directory "$lessDir"; then
      return $?
    fi
  done
}

for dir in ./zesk ./modules; do
  find $dir -name less -type d -print0 | build_less_directories
done

finish
