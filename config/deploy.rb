set :user,        "root"

if !ENV['env'].nil? then
  set(:env, ENV['env'])
else
  set(:env, 'staging')
end

if !env.nil? && env == "production" then
 set :application, "thegoodlab.com"
 set :domain,      "thegoodlab.com"
else   # add more as needed 
 set :application, "thegoodlab.thegoodlab.com"
 set :domain,      "thegoodlab.thegoodlab.com"
end

#set :repository,  "git@github.com:bryantAXS/thegoodlab.git"
set :deploy_to,   "/var/www/#{domain}"
set :shared_path, "#{deploy_to}/shared"
set :use_sudo, false
 
set :scm,        :git
set :branch,     'master'
ssh_options[:forward_agent] = true
set :deploy_via, :remote_cache
 
role :web, "173.230.134.31"
role :app, "173.230.134.31" # this can be the same as the web server
role :db,  "173.230.134.31", :primary => true # this can be the same as the web server
 
namespace :deploy do
  task :start do ; end
  task :stop do ; end
  task :restart, :roles => :app, :except => { :no_release => true } do
    run "#{try_sudo} /etc/init.d/lsws reload" # we use LiteSpeed Web Server
  end
end
 
# The task below serves the purpose of creating symlinks for asset files.
# Large asset files like user uploaded contents and images should not be checked into the repository anyway, so you should move them to a shared location.
 
task :create_symlinks, :roles => :web do
  run "ln -s #{shared_path}/uploads #{current_release}/uploads"
  run "ln -s #{shared_path}/zb #{current_release}/zb"
end
 
# Let's run the task immediately after the deployment is finalised.
 
after "deploy:finalize_update", :create_symlinks