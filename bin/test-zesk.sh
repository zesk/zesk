#!/bin/bash
export APPLICATION_ROOT="$(cd $(dirname "$BASH_SOURCE")/..; pwd)"
cd $APPLICATION_ROOT
composer update
./bin/zesk --Database::names::default=mysqli://root:@127.0.0.1/test_zesk test --debug --verbose --sandbox
