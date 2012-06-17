#!/bin/bash

if [ -z "$1" ]
then
	ref="master"
else
	ref="$1"
fi

DIR="$( cd -P "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
version=`$DIR/get-version.sh`
version_full="yawf-${version}"

# get info about repsitory owner
user_name="`git config --get user.name || true`"
user_mail="`git config --get user.email || true`"
pkg_mark="`git config --get user.pkg-mark || true`"

if [ -z "$user_name" ] || [ -z "$user_mail" ]; then
	echo "Please set up your name and e-mail" >&2
	return 1
else
	user_name_mail="$user_name <$user_mail>"
fi

# aliases
prefix=""
base_dir=`git rev-parse --show-toplevel`
pkg_dir="${base_dir}/packages"
pkg_file="${prefix}${version_full}.tar"
pkg_file_temp="${pkg_dir}/temp/${pkg_file}"
pkg_file_full="${pkg_dir}/${pkg_file}"

if [ ! -d "${pkg_dir}/temp" ]; then
	mkdir -p "${pkg_dir}/temp"
fi

# remove older package
rm -f ${pkg_file_full}.bz2

# extract from git
cd "${base_dir}/www/"
git archive --format tar master > ${pkg_file_temp}

# extract git output
cd ${pkg_dir}/temp
tar -xf ${pkg_file}
cd ${base_dir}/www

# build log
git log > $pkg_dir/temp/.changelog

# build checksum
git_files=`git ls-files`
cd ${pkg_dir}/temp/
for name in $git_files
do
	if [[ $name != 'install.php' ]] && [[ $name != 'etc/current/checksum' ]] && [[ $name != 'etc/current/changelog' ]]; then
		md5sum $name >> ${pkg_dir}/temp/.checksum
	fi
done

# update version
verfile=${pkg_dir}/temp/etc/current/version.ini
mkdir ${pkg_dir}/temp/etc/current &> /dev/null
echo "short_name=${project_nick}" > ${verfile}
echo "name=${project_name}" >> ${verfile}
echo "version=${version}" >> ${verfile}
cd ${pkg_dir}/temp

# erase unnecessary files
#tar --delete -f ${pkg_file_temp} install.php


# save new version
cp ${verfile} ${base_dir}/www/etc/current/

# add install files to package
tar -rf "${pkg_file}" ".changelog"
tar -rf "${pkg_file}" ".checksum"
tar -rf "${pkg_file}" "etc/current/version"

# bzip everything
bzip2 ${pkg_file}
mv ${pkg_file}.bz2 ../
echo "Balíček \"${prefix}${version_full}\" úspěšně vytvořen $pkg_dir" 2>&1

# clean up
rm -Rf ${pkg_dir}/temp
