import { AfterViewInit, Component, ElementRef, Input, OnInit, ViewChild } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { TranslateService } from '@ngx-translate/core';
import { FunctionsService } from '@service/functions.service';
import { HeaderService } from '@service/header.service';
import { COMMA, ENTER } from '@angular/cdk/keycodes';
import { FormControl } from '@angular/forms';
import { MatAutocompleteSelectedEvent, MatAutocomplete } from '@angular/material/autocomplete';
import { Observable, of } from 'rxjs';
import { catchError, filter, map, tap } from 'rxjs/operators';
import { NotificationService } from '@service/notification/notification.service';
import { ConfirmComponent } from '@plugins/modal/confirm.component';
import { MatDialog } from '@angular/material/dialog';

@Component({
    selector: 'app-input-correspondent-group',
    templateUrl: './input-correspondent-group.component.html',
    styleUrls: ['./input-correspondent-group.component.scss'],
})

export class InputCorrespondentGroupComponent implements OnInit, AfterViewInit {

    @Input() id: string;
    @Input() type: string;

    visible = true;
    separatorKeysCodes: number[] = [ENTER, COMMA];
    correspondentGroupsForm = new FormControl();
    filteredcorrespondentGroups: Observable<string[]>;
    correspondentGroups: any[] = [];
    allCorrespondentGroups: any[] = [];

    @ViewChild('correspondentGroupsInput') correspondentGroupsInput: ElementRef<HTMLInputElement>;
    @ViewChild('auto') matAutocomplete: MatAutocomplete;

    constructor(
        public translate: TranslateService,
        public http: HttpClient,
        private notify: NotificationService,
        public functionsService: FunctionsService,
        public headerService: HeaderService,
        public dialog: MatDialog,
    ) {
        this.filteredcorrespondentGroups = this.correspondentGroupsForm.valueChanges.pipe(
            map((item: any | null) => item ? this._filter(item) : this.allCorrespondentGroups));
    }
    async ngOnInit(): Promise<void> {
        await this.getAllCorrespondentsGroup();
        this.getCorrespondentsGroup();
    }

    ngAfterViewInit(): void {
        this.correspondentGroupsForm.setValue(' ');
        setTimeout(() => {
            this.correspondentGroupsForm.setValue('');
        }, 0);
    }

    getAllCorrespondentsGroup() {
        return new Promise((resolve, reject) => {
            this.http.get('../rest/contactsGroups').pipe(
                tap((data: any) => {
                    this.allCorrespondentGroups = data.contactsGroups.map((grp: any) => ({ id: grp.id, label: grp.label }));
                    resolve(true);
                }),
                catchError((err: any) => {
                    this.notify.handleSoftErrors(err);
                    return of(false);
                })
            ).subscribe();
        });
    }

    getCorrespondentsGroup() {
        this.http.get('../rest/contactsGroupsCorrespondents', { params: { 'correspondentId': this.id, 'correspondentType': this.type } }).pipe(
            tap((data: any) => {
                data.contactsGroups.forEach((grp: any) => {
                    this.correspondentGroups.push(grp);
                    const index = this.correspondentGroups.indexOf(grp.id);
                    this.allCorrespondentGroups.splice(index, 1);
                });
            }),
            catchError((err: any) => {
                this.notify.handleSoftErrors(err);
                return of(false);
            })
        ).subscribe();
    }

    remove(item: any): void {

        const dialogRef = this.dialog.open(ConfirmComponent, { panelClass: 'maarch-modal', autoFocus: false, disableClose: true, data: { title: this.translate.instant('lang.delete'), msg: this.translate.instant('lang.confirmAction') } });

        dialogRef.afterClosed().pipe(
            filter((data: string) => data === 'ok'),
            tap(() => {
                const index = this.correspondentGroups.indexOf(item);
                this.allCorrespondentGroups.push(item);
                if (index >= 0) {
                    this.correspondentGroups.splice(index, 1);
                    this.unlinkGrp(item.id);
                }
            }),
            catchError((err: any) => {
                this.notify.handleSoftErrors(err);
                return of(false);
            })
        ).subscribe();
    }

    selected(event: MatAutocompleteSelectedEvent): void {
        const element = this.allCorrespondentGroups.filter((item: any) => item.label === event.option.viewValue)[0];
        this.correspondentGroups.push(element);
        this.allCorrespondentGroups.splice(event.option.value, 1);
        this.correspondentGroupsInput.nativeElement.value = '';
        this.correspondentGroupsForm.setValue(null);
        this.linkGrp(element.id);
    }

    linkGrp(groupId: number) {
        this.http.post('../rest/contactsGroups/' + groupId + '/correspondents', { 'correspondents': this.formatCorrespondents() })
            .subscribe((data: any) => {
                this.notify.success(this.translate.instant('lang.contactAdded'));
            }, (err) => {
                this.notify.error(err.error.errors);
            });
    }

    unlinkGrp(groupId: number) {

        this.http.request('DELETE', `../rest/contactsGroups/${groupId}/correspondents`, { body: { correspondents: this.formatCorrespondents() } }).pipe(
            tap(() => {
                this.notify.success(this.translate.instant('lang.contactDeletedFromGroup'));
            }),
            catchError((err: any) => {
                this.notify.handleSoftErrors(err);
                return of(false);
            })
        ).subscribe();
    }

    formatCorrespondents() {
        return [
            {
                id: this.id,
                type: this.type
            }
        ];
    }

    private _filter(value: any): string[] {
        return this.allCorrespondentGroups.filter((item: any) => item.label.toLowerCase().indexOf(value) > -1);
    }
}
