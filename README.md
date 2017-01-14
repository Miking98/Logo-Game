# [The Logo Game](http://thelogogame.net)

An online version of the Logo Game. Hosted at: http://thelogogame.net/

##Technology

Written in PHP, SQL, JavaScript, HTML, and CSS. Unlike other online versions of the Logo Game, no Flash or other slow plugins were used.

##Inspiration

I wanted to learn a popular framework, like CakePHP or Django, by building a website with one of them. However, I had a hard time wrapping my head around the Model-View-Controller (or Template) design pattern behind these two frameworks, and thus couldn't fully understand the reasons behind many of these framework's design choices.

With programming, I've often found that the best way to understand something is to take it apart and try to build it yourself, so to better understand MVC, I decided to build myself an MVC-style website from scratch. Additionally, my grandma loves the Logo Game iPhone app, but there's no online version of the game that runs in just native HTML and JS. Also, I though thte gameplay could be improved (the iPhone version of the Logo Game is single-player, offering no multi-user competition or  statistics on how fast you guess certain logos. I thought it would be interesting to compile and analyze statistics on which logos people guess the quickest.) Thus, TheLogoGame.net was my effort to achieve these goals.


##Gameplay

Users must race against the clock to guess random logos as quickly as possible. Once solved, the logo will be added to the users's "Collection," and a short information panel about the logo's company/organization will be asynchronously loaded from Wikipedia.

This is slightly different than other versions of the Logo Game, in that users can only see one logo at once, can "Pass" logos they don't know, and "win" by solving them as fast as possible. The website also generates a leaderboard of users and statistical rankings for each logo, showing how fast users guess them.
