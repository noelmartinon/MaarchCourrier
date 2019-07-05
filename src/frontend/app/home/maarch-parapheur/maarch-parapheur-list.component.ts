import { Component, OnInit, ViewChild, Output, EventEmitter } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { LANG } from '../../translate.component';
import { MatDialog, MatTableDataSource, MatPaginator, MatSort } from '@angular/material';
import { NotificationService } from '../../notification.service';
import { HeaderService } from '../../../service/header.service';
import { finalize } from 'rxjs/operators';

export interface MPDocument {
    id: string;
    title: string;
    reference: string;
    mode: string;
    owner: boolean;
}

declare function $j(selector: any): any;

@Component({
    selector: 'app-maarch-parapheur-list',
    templateUrl: "maarch-parapheur-list.component.html",
    styleUrls: ['maarch-parapheur-list.component.scss'],
    providers: [NotificationService]
})
export class MaarchParapheurListComponent implements OnInit {

    lang: any = LANG;
    loading: boolean = true;

    userList: MPDocument[] = [];

    dataSource: MatTableDataSource<MPDocument>;

    displayedColumns: string[] = ['id', 'title'];
    maarchParapheurUrl: string = '';

    @Output() triggerEvent = new EventEmitter<string>();
    
    constructor(public http: HttpClient, public dialog: MatDialog, private notify: NotificationService, private headerService: HeaderService) {
        this.dataSource = new MatTableDataSource(this.userList);
    }

    ngOnInit(): void {
        this.loading = true;
    }

    ngAfterViewInit(): void {
        this.http.get("../../rest/home/maarchParapheurDocuments")
            .pipe(
                finalize(() => this.loading = false)
            )
            .subscribe((data: any) => {
                setTimeout(() => {
                    this.dataSource = new MatTableDataSource(data.documents);
                    this.maarchParapheurUrl = data.url;
                    this.triggerEvent.emit(data.count.current);
                }, 0);
            }, (err: any) => {
                this.notify.handleErrors(err);
            });
    }

    goTo(row: any) {
        window.open(this.maarchParapheurUrl + '/dist/index.html#/documents/' + row.id, '_blank');
    }
}
