import { Injectable } from '@angular/core';
import { TranslateService } from '@ngx-translate/core';
import { HttpClient } from '@angular/common/http';
import { catchError } from 'rxjs/internal/operators/catchError';
import { tap } from 'rxjs/internal/operators/tap';
import { FunctionsService } from './functions.service';
import { NotificationService } from './notification/notification.service';
import { of } from 'rxjs/internal/observable/of';
import { finalize } from 'rxjs/operators';

@Injectable({
    providedIn: 'root',
})
export class IndexingFieldsService {

    coreFields: any[] = [
        {
            identifier: 'doctype',
            label: this.translate.instant('lang.doctype'),
            icon: 'fa-suitcase',
            unit: 'mail',
            type: 'select',
            system: true,
            mandatory: true,
            enabled: true,
            default_value: '',
            values: []
        },
        {
            identifier: 'subject',
            label: this.translate.instant('lang.subject'),
            icon: 'fa-question',
            unit: 'mail',
            type: 'string',
            system: true,
            mandatory: true,
            enabled: true,
            default_value: '',
            values: []
        },
    ];

    fields: any[] = [
        {
            identifier: 'resId',
            label: this.translate.instant('lang.getResId'),
            icon: 'fa-envelope',
            type: 'integer',
            default_value: [],
            values: [],
            enabled: true,
        },
        {
            identifier: 'recipients',
            label: this.translate.instant('lang.getRecipients'),
            icon: 'fa-user',
            type: 'autocomplete',
            default_value: [],
            values: [],
            enabled: true,
        },
        {
            identifier: 'priority',
            label: this.translate.instant('lang.priority'),
            icon: 'fa-traffic-light',
            type: 'select',
            default_value: null,
            values: [],
            enabled: true,
        },
        {
            identifier: 'confidentiality',
            label: this.translate.instant('lang.confidential'),
            icon: 'fa-user-secret',
            type: 'radio',
            default_value: null,
            values: [{ 'id': true, 'label': this.translate.instant('lang.yes') }, { 'id': false, 'label': this.translate.instant('lang.no') }],
            enabled: true,
        },
        {
            identifier: 'initiator',
            label: this.translate.instant('lang.initiatorEntityAlt'),
            icon: 'fa-user',
            type: 'select',
            default_value: null,
            values: [],
            enabled: true,
        },
        {
            identifier: 'departureDate',
            label: this.translate.instant('lang.departureDate'),
            icon: 'fa-calendar-check',
            type: 'date',
            default_value: null,
            values: [],
            enabled: true,
        },
        {
            identifier: 'processLimitDate',
            label: this.translate.instant('lang.processLimitDate'),
            icon: 'fa-stopwatch',
            type: 'date',
            default_value: null,
            values: [],
            enabled: true,
        },
        {
            identifier: 'tags',
            label: this.translate.instant('lang.tags'),
            icon: 'fa-tags',
            type: 'autocomplete',
            default_value: [],
            values: ['/rest/autocomplete/tags', '/rest/tags'],
            enabled: true,
        },
        {
            identifier: 'senders',
            label: this.translate.instant('lang.getSenders'),
            icon: 'fa-address-book',
            type: 'autocomplete',
            default_value: [],
            values: ['/rest/autocomplete/correspondents'],
            enabled: true,
        },
        {
            identifier: 'destination',
            label: this.translate.instant('lang.destination'),
            icon: 'fa-sitemap',
            type: 'select',
            default_value: null,
            values: [],
            enabled: true,
        },
        {
            identifier: 'folders',
            label: this.translate.instant('lang.folders'),
            icon: 'fa-folder',
            type: 'autocomplete',
            default_value: [],
            values: ['/rest/autocomplete/folders', '/rest/folders'],
            enabled: true,
        },
        {
            identifier: 'documentDate',
            label: this.translate.instant('lang.docDate'),
            icon: 'fa-calendar-day',
            unit: 'mail',
            type: 'date',
            default_value: null,
            values: [],
            enabled: true,
        },
        {
            identifier: 'arrivalDate',
            label: this.translate.instant('lang.arrivalDate'),
            icon: 'fa-calendar',
            unit: 'mail',
            type: 'date',
            default_value: null,
            values: [],
            enabled: true,
        },
        {
            identifier: 'registeredMail_type',
            label: this.translate.instant('lang.registeredMailType'),
            icon: 'fa-file',
            type: 'select',
            default_value: null,
            values: [{ 'id': '2D', 'label': this.translate.instant('lang.registeredMail_2D') }, { 'id': '2C', 'label': this.translate.instant('lang.registeredMail_2C') }, { 'id': 'RW', 'label': this.translate.instant('lang.registeredMail_RW') }],
            enabled: true,
        },
        {
            identifier: 'registeredMail_issuingSite',
            label: this.translate.instant('lang.issuingSite'),
            icon: 'fa-warehouse',
            type: 'issuingSite',
            default_value: null,
            values: [],
            enabled: true,
        },
        {
            identifier: 'registeredMail_number',
            label: this.translate.instant('lang.registeredMailNumber'),
            icon: 'fa-barcode',
            type: 'string',
            default_value: null,
            values: [],
            enabled: false,
        },
        {
            identifier: 'registeredMail_warranty',
            label: this.translate.instant('lang.warrantyLevel'),
            icon: 'fa-shield-alt',
            type: 'radio',
            default_value: null,
            values: [{ 'id': 'R1', 'label': 'R1' }, { 'id': 'R2', 'label': 'R2' }, { 'id': 'R3', 'label': 'R3' }],
            enabled: true,
        },
        {
            identifier: 'registeredMail_letter',
            label: this.translate.instant('lang.letter'),
            icon: 'fa-envelope',
            type: 'radio',
            default_value: null,
            values: [{ 'id': true, 'label': this.translate.instant('lang.yes') }, { 'id': false, 'label': this.translate.instant('lang.no') }],
            enabled: true,
        },
        {
            identifier: 'registeredMail_recipient',
            label: this.translate.instant('lang.registeredMailRecipient'),
            icon: 'fa-address-book',
            type: 'registeredMailRecipient',
            default_value: null,
            values: [],
            enabled: true,
        },
        {
            identifier: 'registeredMail_reference',
            label: this.translate.instant('lang.registeredMailReference'),
            icon: 'fa-dolly-flatbed',
            type: 'string',
            default_value: null,
            values: [],
            enabled: true,
        },
        {
            identifier: 'registeredMail_receivedDate',
            label: this.translate.instant('lang.registeredMailReceivedDate'),
            icon: 'fa-calendar-check',
            type: 'date',
            default_value: null,
            values: [],
            enabled: true,
        },
    ];

    customFields: any[] = [];

    roleFields: any[] = [];

    constructor(
        public http: HttpClient,
        public translate: TranslateService,
        private notify: NotificationService,
        public functions: FunctionsService) { }

    getCoreFields() {
        return this.coreFields;
    }

    getFields() {
        return this.fields;
    }

    getCustomFields() {
        return new Promise((resolve, reject) => {
            if (this.customFields.length > 0) {
                resolve(this.customFields);
            } else {
                this.http.get('../rest/customFields').pipe(
                    tap((data: any) => {
                        this.customFields = data.customFields.map((info: any) => {
                            info.identifier = 'indexingCustomField_' + info.id;
                            info.icon = 'fa-hashtag';
                            info.system = false;
                            info.enabled = true;
                            info.SQLMode = info.SQLMode;
                            if (['integer', 'string', 'date'].indexOf(info.type) > -1 && !this.functions.empty(info.values)) {
                                info.default_value = info.values[0].key;
                            } else {
                                info.default_value = info.type === 'banAutocomplete' ? [] : null;
                            }
                            info.values = info.values.length > 0 ? info.values.map((custVal: any) => {
                                return {
                                    id: custVal.key,
                                    label: custVal.label
                                };
                            }) : info.values;
                            return info;
                        });
                    }),
                    finalize(() => resolve(this.customFields)),
                    catchError((err: any) => {
                        this.notify.handleSoftErrors(err);
                        return of(false);
                    })
                ).subscribe();
            }
        });
    }

    getField(identifier: string) {
        let mergedFields = this.getCoreFields().concat(this.getFields());
        mergedFields = mergedFields.concat(this.customFields);
        mergedFields = mergedFields.concat(this.roleFields);

        return mergedFields.filter(field => field.identifier === identifier)[0];
    }

    async getAllFields() {
        const customFields = await this.getCustomFields();
        const roleFields = await this.getRolesFields();

        let mergedFields = this.getCoreFields().concat(this.getFields());
        mergedFields = mergedFields.concat(customFields);
        mergedFields = mergedFields.concat(roleFields);

        return mergedFields;
    }

    getRolesFields() {
        return new Promise((resolve, reject) => {
            this.http.get(`../rest/roles`).pipe(
                tap((data: any) => {
                    const fields: any[] = [];
                    data.roles.forEach((role: any) => {
                        fields.push({
                            identifier: `role_${role.id}`,
                            label: role.label,
                            icon: role.id === 'dest' ? 'fa-user-edit' : 'fa-users',
                            type: 'select',
                            default_value: null,
                            values: [],
                            enabled: true,
                        });
                    });
                    this.roleFields = fields;
                }),
                finalize(() => resolve(this.roleFields)),
                catchError((err: any) => {
                    this.notify.handleSoftErrors(err);
                    return of(false);
                })
            ).subscribe();
        });
    }
}
