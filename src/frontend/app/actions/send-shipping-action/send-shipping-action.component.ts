import { Component, OnInit, Inject, ViewChild } from '@angular/core';
import { LANG } from '../../translate.component';
import { NotificationService } from '../../notification.service';
import { MAT_DIALOG_DATA, MatDialogRef } from '@angular/material';
import { HttpClient } from '@angular/common/http';
import { NoteEditorComponent } from '../../notes/note-editor.component';

@Component({
    templateUrl: "send-shipping-action.component.html",
    styleUrls: ['send-shipping-action.component.scss'],
    providers: [NotificationService],
})
export class SendShippingActionComponent implements OnInit {

    lang: any = LANG;
    loading: boolean = false;

    shippings: any[] = [{
        label: '',
        description: '',
        options: {
            shapingOptions: [],
            sendMode: '',
        },
        fee: 0,
        account: {
            id: '',
            password: ''
        },
    }];

    currentShipping: any = null;

    entitiesList: string[] = [];
    attachList: any[] = [];

    mailsNotSend: any[] = []

    @ViewChild('noteEditor') noteEditor: NoteEditorComponent;

    constructor(public http: HttpClient, private notify: NotificationService, public dialogRef: MatDialogRef<SendShippingActionComponent>, @Inject(MAT_DIALOG_DATA) public data: any) { }

    ngOnInit(): void {
        this.loading = true;

        this.http.post('../../rest/resourcesList/users/' + this.data.currentBasketInfo.ownerId + '/groups/' + this.data.currentBasketInfo.groupId + '/baskets/' + this.data.currentBasketInfo.basketId + '/actions/' + this.data.action.id + '/checkShippings', { resources: this.data.selectedRes })
            .subscribe((data: any) => {
                this.shippings = data.shippingTemplates;
                this.mailsNotSend = data.canNotSend;
                this.entitiesList = data.entities;
                this.attachList = data.resources;
                this.loading = false;
            }, (err: any) => {
                this.notify.handleErrors(err);
                this.loading = false;
            });
    }

    onSubmit(): void {
        this.loading = true;

        let realResSelected: string[] = this.attachList.map((e: any) => { return e.res_id_master; });

        this.http.put('../../rest/resourcesList/users/' + this.data.currentBasketInfo.ownerId + '/groups/' + this.data.currentBasketInfo.groupId + '/baskets/' + this.data.currentBasketInfo.basketId + '/actions/' + this.data.action.id, { resources: realResSelected, data: { shippingTemplateId: this.currentShipping.id }, note: this.noteEditor.getNoteContent() })
            .subscribe((data: any) => {
                if (data && data.errors != null) {
                    this.notify.error(data.errors);
                } else {
                    this.dialogRef.close('success');
                }
                this.loading = false;
            }, (err: any) => {
                this.notify.handleErrors(err);
                this.loading = false;
            });
    }

}
