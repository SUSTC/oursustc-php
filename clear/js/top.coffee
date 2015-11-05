fisherYates = (arr) ->
    i = arr.length

    while --i
        j = Math.floor(Math.random() * (i+1))
        tempi = arr[i]
        tempj = arr[j]
        arr[i] = tempj
        arr[j] = tempi
    return arr

get_ranking = do ->
  rankings = [4, 8, 6, 9, 3, 10, 1, 2, 7, 5]
  (cb) ->
    fisherYates rankings
    data = []
    i = 1
    names = {}
    for r in rankings
      name = ''
      while true
        name = String.fromCharCode(65 + Math.floor(Math.random() * 15))
        break if not names[name]

      names[name] = true

      data.push {
        name: name
        rank: i++
        value: (10 - i) * 13
      }
    cb data

last_data = {}

update = ->
  get_ranking (data) ->
    $('#loading').hide()
    $('#top').show()

    current_data = {}
    later = []
    deleting = []
    element_height = 50
    element_count = 10

    for d in data
      current_data[d.name] = d

    for name, d of current_data
      if not last_data[name]
        d.element = $('<div>').addClass('item')
        d.element.text(name)
        d.element.appendTo '#top'
        d.element.css 'margin-top', element_height * element_count
        d.element.css 'opacity', 0
        later.push d
      else
        d.element = last_data[name].element
        d.element.css 'margin-top', (d.rank - 1) * element_height

    for name, d of last_data
      if not current_data[name]
        d.element.css 'margin-top', element_height * element_count
        d.element.css 'opacity', 0
        deleting.push d

    last_data = current_data
    setTimeout(() ->
      for d in later
        d.element.css 'margin-top', (d.rank - 1) * element_height
        d.element.css 'opacity', 1
        console.log d.name
      later = null
    , 0)
    setTimeout(() ->
      for d in deleting
        d.element.remove()
      deleting = null
    , 2000)
    setTimeout(update, 5000)

$(document).ready ->
  update()
