#!/bin/sh

set -e

DOCUMENTROOT=webroot

if [ -z "${1}" ]; then

	DRUSH="drush"

else
	
	DRUSH=${1}

fi

cd ${DOCUMENTROOT}

echo "Droping database ..."
${DRUSH} sql-drop -y &> /dev/null

if [ -d "sites/default/files" ]; then

	echo "Clean up files directory ..."
	rm -rf sites/default/files/*
	rm -rf sites/defaults/files/.*

fi

echo "Importing database ..."
$(${DRUSH} sql-connect) < ../db/canonical-db.sql 2> /dev/null

echo "Fixing permissions"
chmod -R 777 config
chmod -R 777 sites/default/files

echo "Importing config ..."
${DRUSH} cim -y

echo "Done."
exit 0
