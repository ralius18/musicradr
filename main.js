var script = document.createElement('script')
    script.src = 'resources/jquery-3.4.1.min.js'
    script.type = 'text/javascript'
document.getElementsByTagName('head')[0].appendChild(script)

function add_artist(artist_id, artist_name) {
  // artist_name = $('#' + artist_id + '-heart').closest('table').find('.artist_name').text()
  $.ajax({
    'url': 'add_artist.php',
    'type': 'GET',
    'dataType': 'json',
    'data': {
      artist_id: artist_id,
      artist_name: artist_name
    },
    'success': function (data) {
      a_el = document.getElementById(artist_id + '-heart')
      a_el.setAttribute('onclick', 'remove_artist(\'' + data['link_id'] + '\', \'' + artist_id + '\')')
      a_el.children[0].setAttribute('src', 'resources/heart-filled.svg')
      showToast('Added ' + artist_name + ' to favourite artists')
    },
    'error': function (data) {
      console.error('ERROR')
      console.log(data)
    }
  })
}

function remove_artist(link_id, artist_id) {
  $.ajax({
    'url': 'remove_artist.php',
    'type': 'GET',
    'dataType': 'json',
    'data': {
      link_id: link_id
    },
    'success': function (data) {
      a_el = document.getElementById(artist_id + '-heart')
      artist_name = $('#' + artist_id).children('.artist_name').text()
      a_el.setAttribute('onclick', 'add_artist(\'' + artist_id + '\', \'' + artist_name + '\')')
      a_el.children[0].setAttribute('src', 'resources/heart.svg')
      showToast('Removed ' + artist_name + ' from favourite artists')
    },
    'error': function (data) {
      console.error('ERROR')
      console.log(data)
    }
  })
}

function showToast(message) {
  var toast = document.createElement('div')
  toast.id = 'toast'
  toast.className = 'show'
  toast.innerHTML = message
  document.getElementsByTagName('body')[0].appendChild(toast)
  setTimeout(function () { toast.parentElement.removeChild(toast) }, 3000)
}

function add_album(album_id) {
  $.ajax({
    'url': 'add_album.php',
    'type': 'GET',
    'dataType': 'json',
    'data': {
      album_id: album_id
    },
    'success': function (data) {
      a_el = document.getElementById(album_id + '-heart')
      album_name = $('#' + album_id).children('.album_name').text()
      a_el.setAttribute('onclick', 'remove_album(\'' + data['link_id'] + '\', \'' + album_id + '\')')
      a_el.children[0].setAttribute('src', 'resources/heart-filled.svg')
      showToast('Added ' + album_name + ' to favourite albums')
    },
    'error': function (data) {
      console.error('ERROR')
      console.log(data)
    }
  })
}

function remove_album(link_id, album_id) {
  $.ajax({
    'url': 'remove_album.php',
    'type': 'GET',
    'dataType': 'json',
    'data': {
      link_id: link_id
    },
    'success': function (data) {
      a_el = document.getElementById(album_id + '-heart')
      album_name = $('#' + album_id).children('.album_name').text()
      a_el.setAttribute('onclick', 'add_album(\'' + album_id + '\')')
      a_el.children[0].setAttribute('src', 'resources/heart.svg')
      showToast('Removed ' + album_name + ' from favourite albums')
    },
    'error': function (data) {
      console.error('ERROR')
      console.log(data)
    }
  })
}

function update_album_order() {
  table = $(".sortable_table")
  rows = table.find('tr.album_row')
  new_links = []
  for (i = 0; i < rows.length; i++) {
    new_links[i] = rows[i].id
  }
  $.ajax({
    'url': 'update_ranks.php',
    'type': 'POST',
    'dataType': 'json',
    'data': {
      new_links: new_links
    },
    'success': function (data) {
      showToast('Updated favourite albums')
      $('#buttons').empty() // Remove buttons
    },
    'error': function (data) {
      console.error('ERROR')
      console.log(data)
    }
  })
}

function reset_album_order() {
  document.location.reload()
}

function toggleAlbumArt() {
  // Hide display of image
  display_value = $('#show_art_checkbox').is(":checked") ? 'block' : 'none'
  $('img.album_art').css('display', display_value)

  // Change height of drag placeholder

}