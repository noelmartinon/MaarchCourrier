import { Component, OnInit, Inject, ViewChild } from '@angular/core';
import { LANG } from '../../translate.component';
import { NotificationService } from '../../notification.service';
import { MAT_DIALOG_DATA, MatDialogRef } from '@angular/material';
import { HttpClient } from '@angular/common/http';
import { NoteEditorComponent } from '../../notes/note-editor.component';
import { XParaphComponent } from './x-paraph/x-paraph.component';
import { MaarchParaphComponent } from './maarch-paraph/maarch-paraph.component';

@Component({
    templateUrl: "send-external-signatory-book-action.component.html",
    styleUrls: ['send-external-signatory-book-action.component.scss'],
    providers: [NotificationService],
})
export class SendExternalSignatoryBookActionComponent implements OnInit {

    lang: any = LANG;
    loading: boolean = false;
    additionalsInfos: any = {
        destinationId: '',
        users: [],
        attachments: [],
        noAttachment: []
    };
    signatoryBookEnabled: string = '';

    externalSignatoryBookDatas: any = {
        steps: [],
        objectSent: 'attachment'
    };
    errors: any;

    @ViewChild('noteEditor') noteEditor: NoteEditorComponent;
    
    @ViewChild('xParaph') xParaph: XParaphComponent;
    @ViewChild('maarchParapheur') maarchParapheur: MaarchParaphComponent;

    constructor(public http: HttpClient, private notify: NotificationService, public dialogRef: MatDialogRef<SendExternalSignatoryBookActionComponent>, @Inject(MAT_DIALOG_DATA) public data: any) { }

    ngOnInit(): void {
        this.loading = true;

        this.http.post('../../rest/resourcesList/users/' + this.data.currentBasketInfo.ownerId + '/groups/' + this.data.currentBasketInfo.groupId + '/baskets/' + this.data.currentBasketInfo.basketId + '/checkExternalSignatoryBook', { resources: this.data.selectedRes })
            .subscribe((data: any) => {
                this.additionalsInfos = data.additionalsInfos;
                if (this.additionalsInfos.attachments.length > 0) {
                    this.signatoryBookEnabled = data.signatureBookEnabled;
                }  
                this.errors = data.errors;
                this.loading = false;
                console.log(data);
            }, (err: any) => {
                this.notify.handleErrors(err);
                this.loading = false;
            });
    }

    onSubmit(): void {
        this.loading = true;

        let realResSelected: string[];
        let datas: any;

        realResSelected = this[this.signatoryBookEnabled].getRessources();
        datas = this[this.signatoryBookEnabled].getDatas();

        this.http.put('../../rest/resourcesList/users/' + this.data.currentBasketInfo.ownerId + '/groups/' + this.data.currentBasketInfo.groupId + '/baskets/' + this.data.currentBasketInfo.basketId + '/actions/' + this.data.action.id, { resources: realResSelected, note: this.noteEditor.getNoteContent(), data: datas })
            .subscribe((data: any) => {
                if (!data) {
                    this.dialogRef.close('success');
                }
                if (data && data.errors != null) {
                    this.notify.error(data.errors);
                }
                this.loading = false;
            }, (err: any) => {
                this.notify.handleErrors(err);
                this.loading = false;
            });
    }

    checkValidAction() {
        if (this[this.signatoryBookEnabled] !== undefined) {   
            return this[this.signatoryBookEnabled].checkValidParaph();
        } else {
            return true;
        }
    }
}
