# grandmasusie
Old Family website from 2006

This site had served as a very active location for family engagement.  The benefits were in sharing pictures and 
 commments in an age before facebook, and in having a private repository where more personal photos and stories
 can be shared with your family, and not everyone in the world.
 
Unfortunately, this site came before the age of social media, and is just not as useful and user-friendly as Facebook.
It does, however still have a few good features, conceived long ago, that are not yet implemented in Facebook.

I do claim to have implemented, before I saw them on facebook, on this site:

  1. Social photo sharing.
  2. Easy comment system for photos.
  3. Identifying that photos contain particular people.
  4. Badges showing number of unread stories, and unseen photos, with links to find.
  5. Avatar generation based on previously uploaded content.
  
Facebook has still not implemented:

  1. Identifying the age of users in the photo, based on their birth date, and identication in the photo.
  2. Searching for photos of other users by age, so search results show cousins of a certain age.
  3. Parent structure so underage children don't have their own logins, but tie into their parents' login.
  3. Easy profile switching so parents can allow content posting for underage children, or post on their behalf.

## Configuration Instructions ##

### Site Config ###

The site settings will be read from a config file in the /conf directory.

  1. Create a directory for cached content outside the web root.
  2. Make that directory writable to your apache user so content can be created there.
  3. Copy /conf/config.xml.exmaple to /conf/config.xml
  4. Edit /conf/config.xml with the location of your cache directory.
  5. Repeat for other directories and logs in /conf/config.xml

### Secure RSS ###

The site contains an RSS feed, that uses the same user directory, but with HTTP-BasicAuth

  1. Edit /conf/config.xml with the location of your htpasswd file 
     NOTE: the file will be generated, you just need the location of the file.
  2. Make sure that location is writable
  3. Copy /secure/.htaccess.example to /secure/.htaccess
  4. Configure /secure/.htaccess with the location of your htpasswd file

### DB Config ###

The database settings will be read from a config file in the /conf directory.

  1. Copy /conf/dbconfig.xml.example to /conf/dbconfig.xml
  2. Edit the production fields with the connection information for your database.

The actual database setup files are still a work in progress, and an important dependency if you want to implement this.
TODO: I will post the file to create all the empty tables when I get that ready.

### Static Content ###

Static content like uploaded images and thumbnail versions of them are not a part of this code, and are stored
separately.  You must also create a place for them in your hosting environment.

  1. Create a directory for static content outside the web root.
  2. Make that directory writable to your apache user so content can be created there.
  3. Make a symbolic link to /static. (ln -s ../../static static)

If you are moving a tarball of static content from a previous installation:

  - Create tarball: tar -czf static.tar.gz static/
  - Extract files: tar -xzf static.tar.gz
