Vagrant.configure("2") do |config|
  config.vm.box = "bento/centos-6.7"
  config.vm.box_url = "https://atlas.hashicorp.com/bento/boxes/centos-6.7"
  config.vm.provision :shell, path: "_vagrant/bootstrap.sh"
  config.vm.network :forwarded_port, guest: 80, host: 4567
end

