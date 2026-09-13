#!/usr/bin/env bash
#
# Stand-in for the real `mysqldump` binary, used only by
# tests/Feature/Console/Commands/BackupDatabaseCommandTest.php so the backup
# command can be exercised without a real MySQL server.
#
# Behavior is keyed off the --user= argument BackupDatabaseCommand builds:
#   --user=fail   -> simulates mysqldump failing (non-zero exit, no file)
#   --user=empty  -> simulates mysqldump "succeeding" but producing no output
#   anything else -> writes a one-line dummy dump to --result-file=

user=""
result_file=""

for arg in "$@"; do
    case "$arg" in
        --user=*)
            user="${arg#--user=}"
            ;;
        --result-file=*)
            result_file="${arg#--result-file=}"
            ;;
    esac
done

if [ "$user" = "fail" ]; then
    echo "dummy mysqldump: access denied for user 'fail'" >&2
    exit 1
fi

if [ "$user" = "empty" ]; then
    exit 0
fi

if [ -n "$result_file" ]; then
    printf 'DUMMY SQL CONTENT\n' > "$result_file"
fi

exit 0
