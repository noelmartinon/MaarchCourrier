import { Component, OnInit } from '@angular/core';
import { TranslateService } from '@ngx-translate/core';
import { HttpClient } from '@angular/common/http';
import { KeyValue } from '@angular/common';
import { FormControl, Validators } from '@angular/forms';
import { catchError, debounceTime, filter, map, tap } from 'rxjs/operators';
import { ColorEvent } from 'ngx-color';
import { FunctionsService } from '@service/functions.service';
import {
    amber,
    blue,
    blueGrey,
    brown,
    cyan,
    deepOrange,
    deepPurple,
    green,
    indigo,
    lightBlue,
    lightGreen,
    lime,
    orange,
    pink,
    purple,
    red,
    teal,
    yellow,
} from 'material-colors';
import { ConfirmComponent } from '@plugins/modal/confirm.component';
import { of } from 'rxjs';
import { NotificationService } from '@service/notification/notification.service';
import { MatDialog } from '@angular/material/dialog';

@Component({
    selector: 'app-other-parameters',
    templateUrl: './other-parameters.component.html',
    styleUrls: ['./other-parameters.component.scss'],
})
export class OtherParametersComponent implements OnInit {

    editorsConf: any = {
        java: {},
        onlyoffice: {
            ssl: new FormControl(false),
            uri: new FormControl('192.168.0.11', [Validators.required]),
            port: new FormControl(8765, [Validators.required]),
            token: new FormControl(''),
            authorizationHeader: new FormControl('Authorization')
        },
        collaboraonline: {
            ssl: new FormControl(false),
            uri: new FormControl('192.168.0.11', [Validators.required]),
            port: new FormControl(9980, [Validators.required]),
        }
    };

    watermark = {
        enabled: new FormControl(true),
        text: new FormControl('Copie conforme de [alt_identifier] le [date_now] [hour_now]'),
        posX: new FormControl(30),
        posY: new FormControl(35),
        angle: new FormControl(0),
        opacity: new FormControl(0.5),
        font: new FormControl('helvetica'),
        size: new FormControl(10),
        color: new FormControl([20, 192, 30]),
    };

    editorsEnabled = [];

    fonts = [
        {
            id: 'courier',
            label: 'courier'
        },
        {
            id: 'courierB',
            label: 'courierB'
        },
        {
            id: 'courierI',
            label: 'courierI'
        },
        {
            id: 'courierBI',
            label: 'courierBI'
        },
        {
            id: 'helvetica',
            label: 'helvetica'
        },
        {
            id: 'helveticaB',
            label: 'helveticaB'
        },
        {
            id: 'helveticaI',
            label: 'helveticaI'
        },
        {
            id: 'helveticaBI',
            label: 'helveticaBI'
        },
        {
            id: 'times',
            label: 'times'
        },
        {
            id: 'timesB',
            label: 'timesB'
        },
        {
            id: 'timesI',
            label: 'timesI'
        },
        {
            id: 'timesBI',
            label: 'timesBI'
        },
        {
            id: 'symbol',
            label: 'symbol'
        },
        {
            id: 'zapfdingbats',
            label: 'zapfdingbats'
        }
    ];

    colors: string[] = [
        red['900'],
        red['700'],
        red['500'],
        red['300'],
        red['100'],
        pink['900'],
        pink['700'],
        pink['500'],
        pink['300'],
        pink['100'],
        purple['900'],
        purple['700'],
        purple['500'],
        purple['300'],
        purple['100'],
        deepPurple['900'],
        deepPurple['700'],
        deepPurple['500'],
        deepPurple['300'],
        deepPurple['100'],
        indigo['900'],
        indigo['700'],
        indigo['500'],
        indigo['300'],
        indigo['100'],
        blue['900'],
        blue['700'],
        blue['500'],
        blue['300'],
        blue['100'],
        lightBlue['900'],
        lightBlue['700'],
        lightBlue['500'],
        lightBlue['300'],
        lightBlue['100'],
        cyan['900'],
        cyan['700'],
        cyan['500'],
        cyan['300'],
        cyan['100'],
        teal['900'],
        teal['700'],
        teal['500'],
        teal['300'],
        teal['100'],
        '#194D33',
        green['700'],
        green['500'],
        green['300'],
        green['100'],
        lightGreen['900'],
        lightGreen['700'],
        lightGreen['500'],
        lightGreen['300'],
        lightGreen['100'],
        lime['900'],
        lime['700'],
        lime['500'],
        lime['300'],
        lime['100'],
        yellow['900'],
        yellow['700'],
        yellow['500'],
        yellow['300'],
        yellow['100'],
        amber['900'],
        amber['700'],
        amber['500'],
        amber['300'],
        amber['100'],
        orange['900'],
        orange['700'],
        orange['500'],
        orange['300'],
        orange['100'],
        deepOrange['900'],
        deepOrange['700'],
        deepOrange['500'],
        deepOrange['300'],
        deepOrange['100'],
        brown['900'],
        brown['700'],
        brown['500'],
        brown['300'],
        brown['100'],
        blueGrey['900'],
        blueGrey['700'],
        blueGrey['500'],
        blueGrey['300'],
        blueGrey['100'],
    ];

    constructor(
        public translate: TranslateService,
        public http: HttpClient,
        private dialog: MatDialog,
        private notify: NotificationService,
        public functions: FunctionsService,
    ) { }

    async ngOnInit() {
        await this.getWatermarkConfiguration();
        await this.getEditorsConfiguration();
        Object.keys(this.editorsConf).forEach(editorId => {
            Object.keys(this.editorsConf[editorId]).forEach((elementId: any) => {
                this.editorsConf[editorId][elementId].valueChanges
                    .pipe(
                        debounceTime(1000),
                        filter(() => this.editorsConf[editorId][elementId].valid),
                        tap(() => {
                            this.saveConfEditor();
                        }),
                    ).subscribe();
            });
        });
        Object.keys(this.watermark).forEach(elemId => {
            this.watermark[elemId].valueChanges
                .pipe(
                    debounceTime(1000),
                    tap((value: any) => {
                        this.saveWatermarkConf();
                    }),
                ).subscribe();
        });
    }

    getWatermarkConfiguration() {
        return new Promise((resolve, reject) => {
            this.http.get(`../rest/watermark/configuration`).pipe(
                tap((data: any) => {
                    if (!this.functions.empty(data.configuration)) {
                        this.watermark = {
                            enabled: new FormControl(data.configuration.enabled),
                            text: new FormControl(data.configuration.text),
                            posX: new FormControl(data.configuration.posX),
                            posY: new FormControl(data.configuration.posY),
                            angle: new FormControl(data.configuration.angle),
                            opacity: new FormControl(data.configuration.opacity),
                            font: new FormControl(data.configuration.font),
                            size: new FormControl(data.configuration.size),
                            color: new FormControl(data.configuration.color),
                        };
                    }
                    resolve(true);
                })
            ).subscribe();
        });
    }

    getEditorsConfiguration() {
        return new Promise((resolve, reject) => {
            this.http.get(`../rest/configurations/admin_document_editors`).pipe(
                map((data: any) => data.configuration.value),
                tap((data: any) => {
                    Object.keys(data).forEach(confId => {
                        this.editorsEnabled.push(confId);
                        Object.keys(data[confId]).forEach(itemId => {
                            this.editorsConf[confId][itemId].setValue(data[confId][itemId]);
                        });
                    });
                    resolve(true);
                })
            ).subscribe();
        });
    }

    getInputType(value: any) {
        return typeof value;
    }

    originalOrder = (a: KeyValue<string, any>, b: KeyValue<string, any>): number => {
        return 0;
    }

    addEditor(id: string) {
        this.editorsEnabled.push(id);
        this.saveConfEditor();
    }

    removeEditor(index: number) {
        const dialogRef = this.dialog.open(ConfirmComponent, { panelClass: 'maarch-modal', autoFocus: false, disableClose: true, data: { title: this.translate.instant('lang.delete'), msg: this.translate.instant('lang.confirmAction') } });

        dialogRef.afterClosed().pipe(
            filter((data: string) => data === 'ok'),
            tap(() => {
                this.editorsEnabled.splice(index, 1);
                this.saveConfEditor();
            }),
            catchError((err: any) => {
                this.notify.handleSoftErrors(err);
                return of(false);
            })
        ).subscribe();
    }

    getAvailableEditors() {
        const allEditors = Object.keys(this.editorsConf);
        const availableEditors = allEditors.filter(editor => this.editorsEnabled.indexOf(editor) === -1);
        return availableEditors;
    }

    allEditorsEnabled() {
        return Object.keys(this.editorsConf).length === this.editorsEnabled.length;
    }

    saveWatermarkConf() {
        this.http.put(`../rest/watermark/configuration`, this.formatWatermarkConfig()).pipe(
            tap(() => {
                this.notify.success(this.translate.instant('lang.dataUpdated'));
            }),
            catchError((err: any) => {
                this.notify.handleErrors(err);
                return of(false);
            })
        ).subscribe();
    }

    saveConfEditor() {
        this.http.put(`../rest/configurations/admin_document_editors`, this.formatEditorsConfig()).pipe(
            tap(() => {
                this.notify.success(this.translate.instant('lang.dataUpdated'));
            }),
            catchError((err: any) => {
                this.notify.handleErrors(err);
                return of(false);
            })
        ).subscribe();
    }

    formatWatermarkConfig() {
        const obj: any = {};
        Object.keys(this.watermark).forEach(elemId => {
            obj[elemId] = this.watermark[elemId].value;

        });
        return obj;
    }

    formatEditorsConfig() {
        const obj: any = {};
        this.editorsEnabled.forEach(id => {
            if (this.editorsEnabled.indexOf(id) > -1) {
                obj[id] = {};
                Object.keys(this.editorsConf[id]).forEach(elemId => {
                    obj[id][elemId] = this.editorsConf[id][elemId].value;
                });
            }
        });
        return obj;
    }

    handleChange($event: ColorEvent) {
        this.watermark.color.setValue([$event.color.rgb.r, $event.color.rgb.g, $event.color.rgb.b]);
    }
}
