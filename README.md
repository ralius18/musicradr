![Music Radr](resources/logo_dark.png)

[🎵 Radr](https://musicradr.com) (Music Radar) is a free and simple way to track new releases from your favourite artists.
Once a day, Spotify is checked for any new releases and emails you if an artist you follow has released a new album, single or compilation.
You do not need a Spotify account to use the service. Most artists distribute their new releases on all main streaming services, (Spotify, Google Play Music, Apple Music, etc.) at the same time.
I have taken inspiration and some ideas from [Muspy](https://muspy.com/). Unfortunately, it is no longer maintained, so I have taken it upon myself to create a replacement.
This is still a work in progress with cool features in development, but the intended functionality is active.

The service uses a Ruby program to find new releases as a daily process, and uses the [Spotify Web API](https://developer.spotify.com/documentation/web-api/) to get all the necessary data.

The linking to [AZ Lyrics](https://www.azlyrics.com/) for each song is just an automatic attempt and is not guaranteed to work 100% of the time. This site is not affiliated in any way with AZ Lyrics, they are just the most reliable and simplest lyrics website that I know of. 


A sample config.ini file is in the repository, with it's parent directory made private

DB details