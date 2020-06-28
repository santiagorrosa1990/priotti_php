const fs = require('fs');
var mysql = require('mysql');
const axios = require('axios');
var XLSX = require('xlsx');

fs.readdir('./listas-fabrica/', (err, files) => {
    let fileNames = [];
    files.forEach(file => {
        //Armamos lista con los nombres de las lineas
        addExcelNameIfApplies(file, fileNames);
    });
    var notFound = {};
    let equivalencias = buildEquivalenceTableJsonFromFile('./equivalencias/equiv-fc-fp.xlsx')
    fileNames.forEach(fileName => {
        //Leemos cada lista de precios
        var listaWB = XLSX.readFile('./listas-fabrica/' + fileName);
        var nombreLinea = removeXLSExtension(fileName);
        var precios = priceJson(readFirstSheet(listaWB));
        var preciosOut = [["codigo", "precio"]]
        var linea = equivalencias[nombreLinea]
        var notFounds = [];
        Object.keys(precios).forEach(function (key) {
            if (linea[key]) {
                let codesToUpdate = linea[key];
                codesToUpdate.forEach(code => {
                    preciosOut.push([code, precios[key].toFixed(2)])
                });
            } else {
                notFounds.push(key);
            }
        });
        writeOutput(preciosOut, './listas-para-biglia/' + nombreLinea + '.csv');
        notFound[nombreLinea] = notFounds;
    });

    writeToFile(notFound, './listas-para-biglia/no_encontrados.json');
});

function removeXLSExtension(fileName) {
    if (fileName.includes('.xls')) {
        return fileName.substring(0, fileName.length - 4);
    } else if (fileName.includes('.xlsx')) {
        return fileName.substring(0, fileName.length - 5);
    }
}

function addExcelNameIfApplies(file, list) {
    if (".xlsx" == file.substring(file.length - 5)) {
        list.push(file);
        return
    }
    if (".xls" == file.substring(file.length - 4)) {
        list.push(file);
    }
}

function buildEquivalenceTableJsonFromFile(path) {
    let json = {};
    var workbook = XLSX.readFile(path);
    let sheets = workbook.SheetNames;
    sheets.forEach(sheetName => {
        let sheetJson = XLSX.utils.sheet_to_json(workbook.Sheets[sheetName])
        json[sheetName] = equivJson(sheetJson);
    });
    return json;
}

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

function readFirstSheet(workbook) {
    var sheet_name_list = workbook.SheetNames;
    return XLSX.utils.sheet_to_json(workbook.Sheets[sheet_name_list[0]]);
}

function equivJson(jsonSheet) {
    //in xlsx -> fabrica -- priotti1 -- priotti2 ... -- priotti15
    //out: {"fabrica" : ["priotti1", "priotti2" ...]}
    let json = {};
    jsonSheet.forEach(function (row) {
        let num = 1;
        let list = [];
        let baseName = 'priotti'
        while (row[baseName + num] != null) {
            let value = row[baseName + num];
            list.push(String(value));
            num = num + 1;
        }
        json[row.fabrica] = list
    });
    return json;
}

function priceJson(xlData) {
    //in xlsx -> codigo -- precio
    //out: {"codigo" : "precio"}
    let json = {};
    xlData.forEach(function (row) {
        json[row.codigo] = row.precio
    });
    return json;
}