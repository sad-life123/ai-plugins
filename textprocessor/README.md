cd /var/lms/lms.bsuir.by/www/ai/placement/
mkdir -p textprocessor/{lang/ru,classes/{external,observers},amd/src,templates,db}
# Скопировать все файлы
chmod -R 755 textprocessor/
chown -R www-data:www-data textprocessor/
cd /var/lms/lms.bsuir.by/www
php admin/cli/upgrade.php