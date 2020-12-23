import { Component, Inject, OnInit } from '@angular/core';
import { TranslateService } from '@ngx-translate/core';
import { HttpClient } from '@angular/common/http';
import { MatDialogRef, MAT_DIALOG_DATA } from '@angular/material/dialog';
import { catchError, tap } from 'rxjs/operators';
import { of } from 'rxjs';
import { NotificationService } from '@service/notification/notification.service';

@Component({
    templateUrl: 'signature-position.component.html',
    styleUrls: ['signature-position.component.scss'],
})
export class SignaturePositionComponent implements OnInit {

    loading: boolean = true;

    pages: number[] = [];

    currentUser: number = 0;
    currentPage: number = null;
    currentSignature: any = {
        positionX: 0,
        positionY: 0
    };

    workingAreaWidth: number = 0;
    workingAreaHeight: number = 0;
    formatList: any[] = [
        'dd/MM/y',
        'dd-MM-y',
        'dd.MM.y',
        'd MMM y',
        'd MMMM y',
    ];
    sizes = Array.from({ length: 50 }).map((_, i) => i + 1);
    signList: any[] = [];
    dateList: any[] = [];

    pdfContent: any = null;
    imgContent: any = null;

    today: Date = new Date();
    localDate = this.translate.instant('lang.langISO');
    resizing: boolean = false;

    constructor(
        public translate: TranslateService,
        public http: HttpClient,
        private notify: NotificationService,
        public dialogRef: MatDialogRef<SignaturePositionComponent>,
        @Inject(MAT_DIALOG_DATA) public data: any
    ) { }

    ngOnInit(): void {
        this.currentPage = 1;
        this.getPageAttachment();

        if (this.data.resource.signaturePositions !== undefined) {
            this.signList = this.data.resource.signaturePositions;
        }
        if (this.data.resource.datePositions !== undefined) {
            this.dateList = this.data.resource.datePositions;
        }
    }

    onSubmit() {
        this.dialogRef.close(this.formatData());
    }

    getPageAttachment() {
        console.log(this.data.resource);

        if (this.data.resource.mainDocument) {
            this.http.get(`../rest/resources/${this.data.resource.resId}/thumbnail/${this.currentPage}`).pipe(
                tap((data: any) => {
                    this.pages = Array.from({ length: data.pagesCount }).map((_, i) => i + 1);
                    this.imgContent = 'data:image/png;base64,' + data.fileContent;
                    this.getImageDimensions(this.imgContent);
                }),
                catchError((err: any) => {
                    this.notify.handleSoftErrors(err);
                    return of(false);
                })
            ).subscribe();
        } else {
            this.http.get(`../rest/attachments/${this.data.resource.resId}/thumbnail/${this.currentPage}`).pipe(
                tap((data: any) => {
                    this.pages = Array.from({ length: data.pagesCount }).map((_, i) => i + 1);
                    this.imgContent = 'data:image/png;base64,' + data.fileContent;
                    this.getImageDimensions(this.imgContent);
                }),
                catchError((err: any) => {
                    this.notify.handleSoftErrors(err);
                    return of(false);
                })
            ).subscribe();
        }
    }

    getImageDimensions(imgContent: any): void {
        const img = new Image();
        img.onload = (data: any) => {
            this.workingAreaWidth = data.target.naturalWidth;
            this.workingAreaHeight = data.target.naturalHeight;
        };
        img.src = imgContent;
    }

    moveSign(event: any) {
        const percentx = (event.x * 100) / this.workingAreaWidth;
        const percenty = (event.y * 100) / this.workingAreaHeight;
        this.signList.filter((item: any) => item.sequence === this.currentUser && item.page === this.currentPage)[0].positionX = percentx;
        this.signList.filter((item: any) => item.sequence === this.currentUser && item.page === this.currentPage)[0].positionY = percenty;
    }

    moveDate(event: any) {
        const percentx = (event.x * 100) / this.workingAreaWidth;
        const percenty = (event.y * 100) / this.workingAreaHeight;
        this.dateList.filter((item: any) => item.sequence === this.currentUser && item.page === this.currentPage)[0].positionX = percentx;
        this.dateList.filter((item: any) => item.sequence === this.currentUser && item.page === this.currentPage)[0].positionY = percenty;
    }

    onResizeDateStop(event: any, index: number) {
        this.dateList[index].height = (event.size.height * 100) / this.workingAreaHeight;
        this.dateList[index].width = (event.size.width * 100) / this.workingAreaWidth;
    }

    emptySign() {
        return this.signList.filter((item: any) => item.sequence === this.currentUser && item.page === this.currentPage).length === 0;
    }

    emptyDate() {
        return this.dateList.filter((item: any) => item.sequence === this.currentUser && item.page === this.currentPage).length === 0;
    }

    initSign() {
        this.signList.push(
            {
                sequence: this.currentUser,
                page: this.currentPage,
                positionX: 0,
                positionY: 0
            }
        );
        document.getElementsByClassName('signatureContainer')[0].scrollTo(0, 0);
    }

    initDateBlock() {
        this.dateList.push(
            {
                sequence: this.currentUser,
                page: this.currentPage,
                color: '#666',
                format: 'd MMMM y',
                width: (130 * 100) / this.workingAreaWidth,
                height: (30 * 100) / this.workingAreaHeight,
                positionX: 0,
                positionY: 0
            }
        );
        document.getElementsByClassName('signatureContainer')[0].scrollTo(0, 0);
    }

    getUserSignPosPage(workflowIndex: number) {
        return this.signList.filter((item: any) => item.sequence === workflowIndex);
    }

    selectUser(workflowIndex: number) {
        this.currentUser = workflowIndex;
    }

    getUserName(workflowIndex: number) {
        return this.data.workflow[workflowIndex].labelToDisplay;
    }

    goToSignUserPage(workflowIndex: number, page: number) {
        this.currentUser = workflowIndex;
        this.currentPage = page;
        this.getPageAttachment();
    }

    imgLoaded() {
        this.loading = false;
    }

    deleteSign(index: number) {
        this.signList.splice(index, 1);
    }

    deleteDate(index: number) {
        this.dateList.splice(index, 1);
    }

    formatData() {
        const objToSend: any = {
            signaturePositions: [],
            datePositions: []
        };
        this.data.workflow.forEach((element: any, index: number) => {
            if (this.signList.filter((item: any) => item.sequence === index).length > 0) {
                objToSend['signaturePositions'] = objToSend['signaturePositions'].concat(this.signList.filter((item: any) => item.sequence === index));
            }
            if (this.dateList.filter((item: any) => item.sequence === index).length > 0) {
                objToSend['datePositions'] = objToSend['datePositions'].concat(this.dateList.filter((item: any) => item.sequence === index));
            }
        });
        return objToSend;
    }

    getUserPages() {
        const allList = this.signList.concat(this.dateList);

        return allList;
    }

    hasSign(userSequence: number, page: number) {
        return this.signList.filter((item: any) => item.sequence === userSequence && item.page === page).length > 0;
    }

    hasDate(userSequence: number, page: number) {
        return this.dateList.filter((item: any) => item.sequence === userSequence && item.page === page).length > 0;
    }
}
