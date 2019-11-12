let pWBB4 = require('./index.js');

let ok = pWBB4.install({
    url: 'http://[::1]/WCF/3.0/',
    key: '5d78f6a977b6877026b342dd7fbac3846c512c8835cf30500d7e1124946397ce'
});

pWBB4.getUserID('derpierre65').then((res) => {
    console.log('lel', res);
});