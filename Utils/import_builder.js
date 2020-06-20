const fs = require('fs');
var mysql = require('mysql');
const axios = require('axios');
var XLSX = require('xlsx');

console.log("Inicio de lectura");
console.log("Codigo :::: Precio")

var equivalenciasWB = XLSX.readFile('source/equivalencias.xlsx');
var listaWB = XLSX.readFile('source/lista_nosso.xlsx');

var equivalencias = equivalencias(read(equivalenciasWB));
var precios = lista(read(listaWB));
var preciosOut = [["codigo", "precio"]]

Object.keys(precios).forEach(function (key) {
    if (equivalencias[key]) {
        preciosOut.push([equivalencias[key], precios[key].toFixed(2)])
    }
});

writeOutput(preciosOut);

function writeOutput(ws_data) {
    var ws_name = "salida";
    var wb = XLSX.utils.book_new();
    /* make worksheet */
    var ws = XLSX.utils.aoa_to_sheet(ws_data);
    /* Add the worksheet to the workbook */
    XLSX.utils.book_append_sheet(wb, ws, ws_name);
    XLSX.writeFile(wb, 'salida.csv');
}

function read(workbook) {
    var sheet_name_list = workbook.SheetNames;
    return XLSX.utils.sheet_to_json(workbook.Sheets[sheet_name_list[0]]);
}

function equivalencias(xlData) {
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








