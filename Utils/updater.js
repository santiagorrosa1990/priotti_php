const fs = require('fs');
var mysql = require('mysql');
const axios = require('axios')
var XLSX = require('xlsx')

fs.readdir('./exports/', (err, files) => {
    let dates = [];
    files.forEach(file => {
        dates.push(file.substring(0, file.length - 4));
    });
    dates.sort(function (a, b) { return b - a })
    console.log("mas nuevo: " + dates[0]);
    console.log("segundo mas nuevo: " + dates[1]);
    let actualList = buildListFromExcel('exports/' + dates[1] + '.xls')
    let newList = buildListFromExcel('exports/' + dates[0] + '.xls')
    let data = buildUpdateData(actualList, newList);
    console.log("UPDATE size: " + data.update.length);
    console.log("DELETE size: " + data.delete.length);
    console.log("INSERT size: " + data.insert.length);
    writeToFile(actualList, 'actual.json');
    writeToFile(newList, 'nuevo.json');
    writeToFile(data, 'diff.json');
    //postToSite(data);
});



function postToSite(data) {
    axios.post('http://www.felipepriotti.com.ar/update.php', data)
        .then(function (res) {
            if (res.status == 200) {
                //console.log(res.data);
            }
        })
        .catch(function (err) {
            console.log(err);
        })
        .then(function () {
            console.log("Listo");
        });
}

function buildUpdateData(currentList, newList) {
    let diffList = {
        update: [],
        insert: [],
        delete: [],
        novelties: []
    };
    Object.keys(newList).forEach(function (key) {
        if (currentList[key] != null) {
            if (JSON.stringify(currentList[key]) != JSON.stringify(newList[key])) {
                diffList.update.push(buildChangesOnly(currentList[key], newList[key]));
                diffList.novelties.push(newList[key].marca)
            }
            currentList[key].present = true;
        } else {
            diffList.insert.push(newList[key]);
            diffList.novelties.push(newList[key].marca)
        }
    })
    Object.keys(currentList).forEach(function (key) {
        if (currentList[key].present != true) {
            diffList.delete.push(currentList[key]);
        }
    })
    diffList.novelties = [...new Set(diffList.novelties)]
    return diffList;
};

function buildChangesOnly(oldItem, newItem) {
    diffItem = {};
    diffItem['codigo'] = newItem['codigo'];
    Object.keys(newItem).forEach(function (key) {
        if (JSON.stringify(newItem[key]) != JSON.stringify(oldItem[key])) {
            diffItem[key] = newItem[key];
        };
    });
    return diffItem;
}

function fixCharacters(text) {
    text = (text + '').replace(/�/g, "");
    text = (text + '').replace(/Ñ/g, "NI");
    text = (text + '').replace(/'/g, "`");
    text = (text + '').replace(/\n/g, " ");
    text = (text + '').replace(/\r/g, "");
    text = (text + '').replace(/\t/g, "");
    return text
}

function writeToFile(input, filename) {
    var stringData = JSON.stringify(input);
    filename = './outputs/' + filename;
    fs.writeFile(filename, stringData, 'utf8', function (err) {
        if (err) {
            console.log("An error occured while writing JSON Object to File.");
            return console.log(err);
        }
    });
}

function buildListFromExcel(filename) {
    var workbook = XLSX.readFile(filename);
    var sheet_name_list = workbook.SheetNames;
    var xlData = XLSX.utils.sheet_to_json(workbook.Sheets[sheet_name_list[0]]);
    let json = {};
    xlData.forEach(function (row) {
        var imagen = (((row.arti + '').replace(' ', '_') + '').replace('/', '-') + '').toLowerCase();
        json[row.arti] = {
            codigo: row.arti,
            descripcion: fixCharacters(row.desc),
            marca: row.marca,
            rubro: fixCharacters(row.rubro),
            precio: row.precio,
            precio_oferta: row.oferta,
            info: fixCharacters(buildInfo(row)),
            imagen: imagen
        };
    });
    return json;
}

function buildInfo(row) {
    let out = '';
    for (i = 1; i < 9; i++) {
        key = 'memo' + i;
        if (row[key] != undefined) {
            out = out + row[key];
        }
    };
    return out;
}

