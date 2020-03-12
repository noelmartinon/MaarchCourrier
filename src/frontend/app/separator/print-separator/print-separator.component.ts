import { Component, OnInit, ViewChild } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { LANG } from '../../translate.component';
import { HeaderService } from '../../../service/header.service';
import { NotificationService } from '../../notification.service';
import { MatSidenav } from '@angular/material/sidenav';
import { AppService } from '../../../service/app.service';

declare function $j(selector: any): any;

@Component({
    templateUrl: "print-separator.component.html",
    styleUrls: ['print-separator.component.scss'],
    providers: [NotificationService, AppService],
})
export class PrintSeparatorComponent implements OnInit {

    lang: any = LANG;
    entities: any[] = [];
    entitiesChosen: any[] = [];
    loading: boolean = false;
    docUrl: string = '';
    docData: string = '';
    docBuffer: ArrayBuffer = null;
    separatorTypes: string [] = ['barcode', 'qrcode'];
    separatorTargets: string [] = ['entities', 'generic'];

    separator: any = {
        type : 'qrcode',
        target: 'entities',
        entities: []
    };

    @ViewChild('snav', { static: true }) sidenavLeft: MatSidenav;
    @ViewChild('snav2', { static: true }) sidenavRight: MatSidenav;

    constructor(
        public http: HttpClient, 
        private notify: NotificationService, 
        private headerService: HeaderService,
        public appService: AppService
    ) {
        (<any>window).pdfWorkerSrc = '../../node_modules/pdfjs-dist/build/pdf.worker.min.js';
    }

    ngOnInit(): void {

        this.headerService.setHeader('Impression des séparateurs');

        this.http.get("../../rest/entities")
            .subscribe((data: any) => {
                this.entities = data['entities'];
                this.entities.forEach(entity => {
                    entity.state.disabled = false;
                });
                this.loadEntities();

            }, () => {
                location.href = "index.php";
            });
    }

    loadEntities() {

        setTimeout(() => {
            $j('#jstree')
            .on('select_node.jstree', (e: any, data: any) => {
                this.separator.entities = $j('#jstree').jstree(true).get_checked(); // to trigger disable button if no entities
            })
            .on('deselect_node.jstree', (e: any, data: any) => {
                this.separator.entities = $j('#jstree').jstree(true).get_checked(); // to trigger disable button if no entities
            })
            .jstree({
                "checkbox": {
                    "three_state": false //no cascade selection
                },
                'core': {
                    force_text : true,
                    'themes': {
                        'name': 'proton',
                        'responsive': true
                    },
                    'data': this.entities,
                },
                "plugins": ["checkbox", "search", "sort"]
            });
            var to: any = false;
            $j('#jstree_search').keyup(function () {
                if (to) { clearTimeout(to); }
                to = setTimeout(function () {
                    var v = $j('#jstree_search').val();
                    $j('#jstree').jstree(true).search(v);
                }, 250);
            });
            $j('#jstree')
                // create the instance
                .jstree();
        }, 0);
    }

    generateSeparators() {
        this.loading = true;
        this.separator.entities = $j('#jstree').jstree(true).get_checked();
        this.http.post("../../rest/entitySeparators", this.separator)
            .subscribe((data: any) => {
                this.docData = data;
                this.docBuffer = this.base64ToArrayBuffer(this.docData);
                this.downloadSeparators();
                this.loading = false;
            }, (err: any) => {
                this.notify.handleErrors(err);
            });
    }

    base64ToArrayBuffer(base64: any) {
        var binary_string =  window.atob(base64);
        var len = binary_string.length;
        var bytes = new Uint8Array( len );
        for (var i = 0; i < len; i++)        {
            bytes[i] = binary_string.charCodeAt(i);
        }
        return bytes.buffer;
    }

    changeType(type: any) {
        this.docBuffer = null;
        if (type.value == 'entities') {
            this.entities.forEach(entity => {
                entity.state.disabled = false;
            });
            $j('#jstree').jstree(true).settings.core.data = this.entities;
            $j('#jstree').jstree('deselect_all');
            $j('#jstree').jstree("refresh");
        } else {
            this.entities.forEach(entity => {
                entity.state.disabled = true;
            });
            $j('#jstree').jstree(true).settings.core.data = this.entities;
            $j('#jstree').jstree('deselect_all');
            $j('#jstree').jstree("refresh");
        }
    }

    downloadSeparators() {
        const a = document.createElement('a');
        document.body.appendChild(a);
        a.style.display = 'none';

        const url = `data:application/pdf;base64,${this.docData}`;
        a.href = url;

        let today: any;
        let dd: any;
        let mm: any;
        let yyyy: any;

        today = new Date();
        dd = today.getDate();
        mm = today.getMonth() + 1;
        yyyy = today.getFullYear();

        if (dd < 10) {
            dd = '0' + dd;
        }
        if (mm < 10) {
            mm = '0' + mm;
        }
        today = dd + '-' + mm + '-' + yyyy;

        a.download = this.lang.separators + "_" + today + ".pdf";
        a.click();
        window.URL.revokeObjectURL(url);
    }
}
