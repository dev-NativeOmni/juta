#!/usr/bin/env bash

# This script acts as a mock for mysqldump in tests.

fail=0
empty=0
result_file=""

for arg in "$@"; do
    if [[ $arg == "--user=fail" ]]; then
        fail=1
    elif [[ $arg == "--user=empty" ]]; then
        empty=1
    elif [[ $arg == --result-file=* ]]; then
        result_file="${arg#*=}"
    fi
done

if [[ $fail -eq 1 ]]; then
    echo "Mock failure" >&2
    exit 1
fi

if [[ -n "$result_file" ]]; then
    if [[ $empty -eq 1 ]]; then
        touch "$result_file"
    else
        echo "DUMMY SQL CONTENT" > "$result_file"
    fi
fi

exit 0
