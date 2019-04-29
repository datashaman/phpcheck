import Vue from 'vue'
import ClipboardJS from 'clipboard'
import $ from 'domtastic'
import FuzzySearch from 'fuzzy-search'

require('../styles/functions.scss')

Array.prototype.diff = function (a) {
    return this.filter(function(i) {return a.indexOf(i) < 0;})
}

const functions = require('../data/functions.json')

const allFunctions = Object
    .keys(functions)
    .map(file => functions[file])
    .flat()

$(document).ready(function () {
    Object
        .keys(functions)
        .forEach(function (file) {
            functions[file]
                .forEach(function (f) {
                    let $f = $('#menu-item-template').children().clone()
                    $f.find('a').attr('href', f.href)
                    $f.find('.shortName').text(f.shortName)
                    $f.attr('id', 'menu-item-' + f.shortName)
                      .appendTo('#menu-' + file)
                      .css('display', null)
                })
        })

    new ClipboardJS('.copy')

    const searcher = new FuzzySearch(allFunctions, ['shortName', 'summary'])

    $('#search input').on('keyup', function (evt) {
        const results = searcher.search($(this).val())

        allFunctions.forEach(function (f) {
            let display = results.indexOf(f) >= 0
            let id = 'menu-item-' + f.shortName
            $('#' + id).css('display', display ? null : 'none')
        })
    })
})
