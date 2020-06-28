const fs = require('fs');
var mysql = require('mysql');
const axios = require('axios');
var XLSX = require('xlsx');

console.log("Inicio de lectura");

fs.readdir('./../listas-fabrica/', (err, files) => {
    let lineas = [];
    files.forEach(file => {
        if (".xlsx" == file.substring(file.length - 5)) {
            lineas.push(file.substring(0, file.length - 5));
        }
    });
    var notFound = {};
    lineas.forEach(linea => {
        console.log("Linea: " + linea);
        var listaWB = XLSX.readFile('../listas-fabrica/' + linea + '.xlsx');
        var precios = lista(readFirstSheet(listaWB));
        var equivalenciasWB = XLSX.readFile('../equivalencias/equiv-fc-fp.xlsx');
        var equivalencias = buildEquiv(readSheet(equivalenciasWB, linea));
        console.log("Equivalencias" + JSON.stringify(equivalencias))
        var preciosOut = [["codigo", "precio"]]
        var notFounds = [];

        Object.keys(precios).forEach(function (key) {
            if (equivalencias[key]) {
                preciosOut.push([equivalencias[key], precios[key].toFixed(2)])
            } else {
                notFounds.push(key);
            }

        });
        writeOutput(preciosOut, './../listas-para-biglia/' + linea + '.csv');
        notFound[linea] = notFounds;
    });

    writeToFile(notFound, './../listas-para-biglia/no_encontrados.json');
});

function writeOutput(ws_data, name) {
    var ws_name = "salida";
    var wb = XLSX.utils.book_new();
    /* make worksheet */
    var ws = XLSX.utils.aoa_to_sheet(ws_data);
    /* Add the worksheet to the workbook */
    XLSX.utils.book_append_sheet(wb, ws, ws_name);
    XLSX.writeFile(wb, name);
}

function writeToFile(input, filename) {
    var stringData = JSON.stringify(input);
    fs.writeFile(filename, stringData, 'utf8', function (err) {
        if (err) {
            console.log("An error occured while writing JSON Object to File.");
            return console.log(err);
        }
    });
}

function readSheet(workbook, name) {
    return XLSX.utils.sheet_to_json(workbook.Sheets[name]);
}

function readFirstSheet(workbook) {
    var sheet_name_list = workbook.SheetNames;
    return XLSX.utils.sheet_to_json(workbook.Sheets[sheet_name_list[0]]);
}

function buildEquiv(xlData) {
    let json = {};
    xlData.forEach(function (row) {
        json[row.fabrica] = row.priotti
    });
    return json;
}

function lista(xlData) {
    let json = {};
    xlData.forEach(function (row) {
        json[row.codigo] = row.precio
    });
    return json;
}