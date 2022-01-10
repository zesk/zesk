#!/usr/bin/env bash
#
# Generic Shell setup for ALL users, ALL environments
#
test "${BASHRC_DEBUG}" && echo "${BASH_SOURCE[0]}"

[ -f /etc/env.sh ] && set -a && . /etc/env.sh && set +a

# Bash settings for all containers
container_prompt() {
  local role
  role=$1
  if [ -z "$role" ]; then
    role=$(cat /etc/docker-role 2> /dev/null || echo -n "no-role")
  fi
  export PS1='⚡️'${role}' \[\e[31;1m\]('${BUILD_CODE:-no-code}') \[\e[34;1m\]\u@\[\e[31;1m\]\h \[\e[0m\]\w\[\e[0m\] > '
}

composer_install() {
  cd /usr/local/bin/
  php -d allow_url_fopen=On composer-installer.php --filename=composer
}

#
# For iTerm terminals
#
badge() {
  local message
  message="$*"
  printf "\e]1337;SetBadgeFormat=%s\a" "$(echo -n "$message" | base64)"
}

#
# Find text in any file in a project from current directory down.
#
#     php-find CONSTANT
#
# Also try:
#
#     php-find -l CONSTANT
#
php-find() {
  local args
  args=
  if [ "$1" = "-l" ]; then
    args=$1
    shift
  fi
  find . -type f \
    \( \
    -name '*.php' \
    -or -name '*.cfg' \
    -or -name '*.classes' \
    -or -name '*.conf' \
    -or -name '*.css' \
    -or -name '*.email' \
    -or -name '*.htm' \
    -or -name '*.html' \
    -or -name '*.inc' \
    -or -name '*.ini' \
    -or -name '*.install' \
    -or -name '*.j2' \
    -or -name '*.js' \
    -or -name '*.jsx' \
    -or -name '*.less' \
    -or -name '*.list' \
    -or -name '*.md' \
    -or -name '*.module' \
    -or -name '*.php4' \
    -or -name '*.php5' \
    -or -name '*.phpt' \
    -or -name '*.po' \
    -or -name '*.psql' \
    -or -name '*.router' \
    -or -name '*.scss' \
    -or -name '*.sh' \
    -or -name '*.sql' \
    -or -name '*.tpl' \
    -or -name '*.yml' \
    \) -and -type f -and -not -name '*.min.js' -print0 |
    xargs -0 grep "$args" "$*"
}

export BLOCKSIZE=M

if [ "$TERM" = "xterm" ]; then
  stty erase '^?'
  stty erase "^H"
fi

MISSING=
if [ -z "$(which vim 2>/dev/null)" ]; then
  MISSING="$MISSING vim"
else
  export EDITOR=/usr/bin/vim
  export VISUAL=/usr/bin/vim
fi
export PAGER=more

if [ -z "$(which aws 2>/dev/null)" ]; then
  MISSING="$MISSING aws"
else
  complete -C '/usr/local/bin/aws_completer' aws
fi

export LS_OPTIONS='--color=auto'

alias ls='ls $LS_OPTIONS'
alias ll='ls $LS_OPTIONS -l'
alias l='ls $LS_OPTIONS -lA'
alias ..='cd ..'
