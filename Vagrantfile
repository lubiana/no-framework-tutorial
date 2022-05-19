# -*- mode: ruby -*-
# vi: set ft=ruby :

Vagrant.configure("2") do |config|
  config.vm.box = "archlinux/archlinux"
  config.vm.provider "virtualbox" do |v|
    v.memory = 512
    v.cpus = 2
  end
  config.vm.network "forwarded_port", guest: 1235, host: 1235
  config.vm.network "forwarded_port", guest: 22, host: 2200, id: 'ssh'
  config.vm.synced_folder "./app", "/home/vagrant/app/"
  config.ssh.username = 'vagrant'
  config.ssh.password = 'vagrant'
  config.vm.provision "shell", inline: <<-SHELL
    pacman -Syu --noconfirm
    pacman -S --noconfirm php php-sqlite php-intl php-sodium php-apcu composer xdebug vim
    echo '127.0.0.1 localhost' >> /etc/hosts
    echo -e 'extension=pdo_sqlite\nextenstion=sqlite3\n' >> /etc/php/conf.d/tutorial.ini
    echo -e 'extension=apcu\nzend_extension=opcache\n' >> /etc/php/conf.d/tutorial.ini
    echo -e 'zend_extension=xdebug\nxdebug.client_host=10.0.2.2\n' >> /etc/php/conf.d/tutorial.ini
    echo -e 'xdebug.client_port=9003\nxdebug.mode=debug\n' >> /etc/php/conf.d/tutorial.ini
    echo -e 'zend.assertions=1\n' >> /etc/php/conf.d/tutorial.ini
    echo -e 'opcache.enable=1\nopcache.enable_cli=1\n' >> /etc/php/conf.d/tutorial.ini
    echo -e 'acp.enable=1\napc.enable_cli=1\n' >> /etc/php/conf.d/tutorial.ini
    echo -e 'extension=intl\n' >> /etc/php/conf.d/tutorial.ini
  SHELL
end
