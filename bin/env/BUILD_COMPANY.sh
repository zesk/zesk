#!/usr/bin/env bash
# Copyright &copy; 2025 Market Acumen, Inc.
# shellcheck source=/dev/null
source "${BASH_SOURCE[0]%/*}/COMPANY_NAME.sh"
export BUILD_COMPANY
BUILD_COMPANY="${BUILD_COMPANY-"$COMPANY_NAME"}"
