#####################################################################################
  # The Good Lab Capistrano Deploy Script
  # Modified from Dan Benjamins version - https://github.com/dan/hivelogic-ee-deploy
#####################################################################################

if !ENV['env'].nil? then
  set(:env, ENV['env'])
else
  set(:env, 'dev')
end

if !env.nil? && env == "production" then

  set :application, "thegoodlab.com"
  set :ee_system, "system/expressionengine"
  set :deploy_to, "/var/www/#{application}"
  set :user, "deploy"
  set :scm_passphrase, "d3pl0y!"  # The deploy user's password

  role :app, "173.230.134.31"
  role :web, "173.230.134.31"
  role :db,  "173.230.134.31", :primary => true

  set :remote_db_host, "127.0.0.1"
  set :remote_db_name, "thegoodlab_prod"
  set :remote_db_user, "bryantjoseph"
  set :remote_db_password, "_barclay7!"

else
  
  set :application, "thegoodlab.thegoodlab.com"
  set :ee_system, "system/expressionengine"
  set :deploy_to, "/var/www/#{application}"
  set :user, "deploy"
  set :scm_passphrase, "d3pl0y!"  # The deploy user's password
  
  role :app, "173.230.134.31"
  role :web, "173.230.134.31"
  role :db,  "173.230.134.31", :primary => true
  
  #DB info
  set :remote_db_host, "127.0.0.1"
  set :remote_db_name, "thegoodlab"
  set :remote_db_user, "bryantjoseph"
  set :remote_db_password, "_barclay7!"
 
end

set :local_db_host, "127.0.0.1"
set :local_db_name, "thegoodlab"
set :local_db_user, "root"
set :local_db_password, "root"

set :repository, "git@codebasehq.com:thegoodlab/the-good-lab-website/website.git"
set :branch, "master"

# Additional SCM settings

default_run_options[:pty] = true  # Must be set for the password prompt from git to work
set :scm, :git
set :ssh_options, { :forward_agent => true }
# set :deploy_via, :remote_cache
# set :copy_strategy, :checkout
set :keep_releases, 3
set :use_sudo, false
set :copy_compression, :bz2

# Deployment process
after "deploy:update", "deploy:cleanup" 
after "deploy", "deploy:create_symlinks", "deploy:set_permissions", "deploy:remove_files"

# Custom deployment tasks
namespace :deploy do

  desc "This is here to overide the original :restart"
  task :restart, :roles => :app do
    # do nothing but overide the default
  end

  task :finalize_update, :roles => :app do
    run "chmod -R g+w #{latest_release}" if fetch(:group_writable, true)
    # overide the rest of the default method
  end

  desc "Create additional EE directories and set permissions after initial setup"
  task :after_setup, :roles => :app do
    
    # create directories in shared folder
    run "mkdir -p #{deploy_to}/#{shared_dir}/config"
    run "mkdir -p #{deploy_to}/#{shared_dir}/images"
    
    # set permissions
    run "chmod 777 #{deploy_to}/#{shared_dir}/config"
    run "chmod 777 #{deploy_to}/#{shared_dir}/images"
  
  end

  desc "Create symlinks to shared data such as config files and uploaded images"
  task :create_symlinks, :roles => :app do
    
    # the config files
    run "cp #{deploy_to}/#{shared_dir}/config/config_bootstrap.php #{current_release}/config_bootstrap.php"
    
    # standard image upload directories
    run "ln -s #{deploy_to}/#{shared_dir}/images #{current_release}"
  end
  
  desc "Set the correct permissions for the config files and cache folder"
  task :set_permissions, :roles => :app do
    
    #set cache permissions
    run "mkdir #{current_release}/#{ee_system}/cache"
    run "chmod -R 777 #{current_release}/#{ee_system}/cache"
    run "chmod 666 #{current_release}/#{ee_system}/config/config.php"
  end
  
  desc "Clear the ExpressionEngine caches"
  task :clear_cache, :roles => :app do
    run "if [ -e #{current_release}/#{ee_system}/cache ]; then rm -r #{current_release}/#{ee_system}/cache/*; fi"
  end
  
  desc "Remove files after deployment"
  task :remove_files, :roles => :web do
      
  end
  
  # Fetches remote server database and loads it into the local database
  # 
  # Assumes you have remote_db_host, remote_db_name, remote_db_user and remote_db_password AND
  # local_db_host, local_db_name, local_db_user and local_db_password set somewhere in your config file
  #
  # Only supports MySQL.
  desc "Load production data into development database"
  task :fetch_remote_db, :roles => :db, :only => { :primary => true } do
    
    filename = "#{remote_db_name}dump.#{Time.now.strftime '%Y-%m-%d_%H:%M:%S'}.sql"

    on_rollback do 
      delete "/tmp/#{filename}" 
      delete "/tmp/#{filename}.gz" 
    end

    cmd = "mysqldump -u #{remote_db_user} -h #{remote_db_host} --password=#{remote_db_password} #{remote_db_name} > /tmp/#{filename}"
    puts "Dumping remote database"
    run(cmd) do |channel, stream, data|
      puts data
    end

    # compress the file on the server
    puts "Compressing remote data"
    run "gzip -9 /tmp/#{filename}"
    puts "Fetching remote data"
    get "/tmp/#{filename}.gz", "dump.sql.gz"

    # build the import command
    # no --password= needed if password is nil. 
    if "#{local_db_password}".nil?
      cmd = "/Applications/MAMP/Library/bin/mysql -u #{local_db_user} #{local_db_name} < dump.sql"
    else
      cmd = "/Applications/MAMP/Library/bin/mysql -u #{local_db_user} --password=#{local_db_password} #{local_db_name} < dump.sql"
    end

    # unzip the file. Can't use exec() for some reason so backticks will do
    puts "Uncompressing dump"
    `gzip -d dump.sql.gz`
    puts "Executing : #{cmd}"
    `#{cmd}`
    puts "Removing dump file"
    `rm -f dump.sql`
    puts "All done! Edit your development settings"
  end

end