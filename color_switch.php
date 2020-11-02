<btn class="btn btn-primary dark-btn" id="mode-btn" style="float: right;" onclick="toggleDark()">
  <img src="resources/moon.svg">&nbspDark&nbspMode
</btn>

<script>
  document.onreadystatechange  = function onLoadPage() {
    setMode(isDarkMode() ? 'dark' : 'light')
  }

  function toggleDark() {
    document.querySelector(":root").style.setProperty('--transition', 'all 0.5s ease-in-out')
    setTimeout(function() {
      setMode(isDarkMode() ? 'light' : 'dark')
    }, 1)
    document.querySelector(":root").style.setProperty('--transition', 'none')
  }

  function setMode(mode) {
    var style = document.querySelector(":root").style
    var toggleBtn = document.querySelector('#mode-btn')
    if (mode === 'light') {
      // Change to light
      style.setProperty('--bg', '#EFEFEF')
      style.setProperty('--bg2', '#DDDDDD')
      style.setProperty('--fg', '#121212')
      style.setProperty('--btn_bg', '#DDD')
      style.setProperty('--fg_green', '#009c22')
      document.cookie = 'darkmode=false;'
      toggleBtn.innerHTML = '<img src="resources/moon.svg">&nbspDark&nbspMode'
    } else {
      // Change to dark
      style.setProperty('--bg', '#121212')
      style.setProperty('--bg2', '#242424')
      style.setProperty('--fg', '#EFEFEF')
      style.setProperty('--btn_bg', '#EFEFEF')
      style.setProperty('--fg_green', '#7aeb93')
      document.cookie = 'darkmode=true;'
      toggleBtn.innerHTML = '<img src="resources/sun.svg">&nbspLight&nbspMode'
    }
  }

  function isDarkMode() {
    var name = 'darkmode'
    var decodedCookie = decodeURIComponent(document.cookie)
    var ca = decodedCookie.split(';')
    var isDark = true
    for(var i = 0; i < ca.length; i++) {
      var c = ca[i]
      while (c.charAt(0) == ' ') {
        c = c.substring(1)
      }
      if (c.indexOf(name) == 0) {
        isDark = c.substring(name.length + 1) === 'true'
      }
    }
    return isDark
  }
</script>