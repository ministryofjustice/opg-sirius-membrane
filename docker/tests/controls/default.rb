title 'Files copied'

control 'Docker Config Files' do
  impact 1
  title 'Container Config'
  desc 'Default config files written to disk'
  describe file('/usr/local/etc/php/conf.d/memory_limit.ini') do
    it { should exist }
  end
  describe file('/usr/local/etc/php-fpm.d/www.conf') do
    it { should exist }
    its('content') { should match %r{ping.path = /ping} }
  end
  describe file('/usr/local/etc/php/conf.d/opcache.ini') do
    it { should exist }
  end
  describe command('whoami') do
    its('stdout') { should eq "www-data\n" }
  end
end

control 'Cache Folders' do
  impact 1
  title 'Cache Folders'
  desc 'Config cache folder exists'
  describe file('/tmp/config') do
    it { should be_directory }
  end
end

control 'PHP Healthcheck' do
  impact 1
  title 'PHP Healthcheck'
  desc 'PHP Healthcheck'
  describe command('/usr/bin/cgi-fcgi').exist? do
    it { should eq true }
  end
  describe command('SCRIPT_NAME=/ping SCRIPT_FILENAME=/ping REQUEST_METHOD=GET cgi-fcgi -bind -connect 127.0.0.1:9000') do
    its('exit_status') { should match 0 }
  end
end
