import { Component, OnInit, Input, ViewChild } from '@angular/core';
import { TranslateService } from '@ngx-translate/core';
import { HttpClient } from '@angular/common/http';
import { VisaWorkflowComponent } from '../../../visa/visa-workflow.component';
import { SignaturePositionComponent } from './signature-position/signature-position.component';
import { catchError, filter, finalize, tap } from 'rxjs/operators';
import { of } from 'rxjs';
import { FunctionsService } from '@service/functions.service';
import { NotificationService } from '@service/notification/notification.service';
import { MatDialog } from '@angular/material/dialog';

@Component({
    selector: 'app-maarch-paraph',
    templateUrl: 'maarch-paraph.component.html',
    styleUrls: ['maarch-paraph.component.scss'],
})
export class MaarchParaphComponent implements OnInit {

    loading: boolean = false;

    currentAccount: any = null;
    usersWorkflowList: any[] = [];

    signaturePositions: any = {};

    injectDatasParam = {
        resId: 0,
        editable: true
    };

    @ViewChild('appVisaWorkflow', { static: true }) appVisaWorkflow: VisaWorkflowComponent;

    @Input() resourcesToSign: any[] = [];
    @Input() additionalsInfos: any;
    @Input() externalSignatoryBookDatas: any;

    constructor(
        public translate: TranslateService,
        private notify: NotificationService,
        public http: HttpClient,
        private functions: FunctionsService,
        public dialog: MatDialog
    ) { }

    ngOnInit(): void {
        if (typeof this.additionalsInfos.destinationId !== 'undefined' && this.additionalsInfos.destinationId !== '') {
            setTimeout(() => {
                this.appVisaWorkflow.loadListModel(this.additionalsInfos.destinationId);
            }, 0);
        }
    }

    isValidParaph() {
        if (this.additionalsInfos.attachments.length === 0 || this.appVisaWorkflow.getWorkflow().length === 0 || this.appVisaWorkflow.checkExternalSignatoryBook().length > 0) {
            return false;
        } else {
            return true;
        }
    }

    getRessources() {
        return this.additionalsInfos.attachments.map((e: any) => e.res_id);
    }

    getDatas() {
        const formatedData: any = { steps: [] };
        const workflow = this.appVisaWorkflow.getWorkflow();

        this.resourcesToSign.forEach((resource: any) => {
            workflow.forEach((element: any, index: number) => {
                formatedData['steps'].push(
                    {
                        'resId': resource.resId,
                        'mainDocument': resource.mainDocument,
                        'externalId': element.externalId.maarchParapheur,
                        'sequence': index,
                        'action': element.requested_signature ? 'sign' : 'visa',
                        'signaturePositions': resource.signaturePositions !== undefined ? resource.signaturePositions : [],
                        'datePositions': resource.datePositions !== undefined ? resource.datePositions : [],
                    }
                );
            });
        });
        return formatedData;
    }

    openSignaturePosition(resource: any) {
        const dialogRef = this.dialog.open(SignaturePositionComponent, {
            height: '99vh',
            panelClass: 'maarch-modal',
            disableClose: true,
            data: {
                resource: resource,
                workflow: this.appVisaWorkflow.getWorkflow()
            }
        });
        dialogRef.afterClosed().pipe(
            filter((res: any) => !this.functions.empty(res)),
            tap((res: any) => {
                this.resourcesToSign.filter((itemToSign: any) => itemToSign.resId === resource.resId && itemToSign.mainDocument === resource.mainDocument)[0]['signaturePositions'] = res.signaturePositions;
                this.resourcesToSign.filter((itemToSign: any) => itemToSign.resId === resource.resId && itemToSign.mainDocument === resource.mainDocument)[0]['datePositions'] = res.datePositions;
            }),
            finalize(() => this.loading = false),
            catchError((err: any) => {
                this.notify.handleErrors(err);
                return of(false);
            })
        ).subscribe();
    }

    hasPositions(resource: any) {

        return (
            this.resourcesToSign.filter((itemToSign: any) => itemToSign.resId === resource.resId && itemToSign.mainDocument === resource.mainDocument)[0]['signaturePositions'] !== undefined &&
            this.resourcesToSign.filter((itemToSign: any) => itemToSign.resId === resource.resId && itemToSign.mainDocument === resource.mainDocument)[0]['signaturePositions'].length > 0)
            ||
            (this.resourcesToSign.filter((itemToSign: any) => itemToSign.resId === resource.resId && itemToSign.mainDocument === resource.mainDocument)[0]['datePositions'] !== undefined &&
                this.resourcesToSign.filter((itemToSign: any) => itemToSign.resId === resource.resId && itemToSign.mainDocument === resource.mainDocument)[0]['datePositions'].length > 0);
    }
}
