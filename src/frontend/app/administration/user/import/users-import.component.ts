import { Component, OnInit, Inject } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { NotificationService } from '../../../../service/notification/notification.service';
import { MAT_DIALOG_DATA, MatDialogRef } from '@angular/material/dialog';
import { TranslateService } from '@ngx-translate/core';
import { MatTableDataSource } from '@angular/material/table';

@Component({
    templateUrl: 'users-import.component.html',
    styleUrls: ['users-import.component.scss']
})
export class UsersImportComponent implements OnInit {

    userColmuns: string[] = [
        'id',
        'userId',
        'firstname',
        'lastname',
        'phone',
        'email'
    ];

    csvColumns: string[] = [

    ];

    associatedColmuns: any = {};
    dataSource = new MatTableDataSource(null);
    csvData: any[] = [];
    userData: any[] = [];

    constructor(
        private translate: TranslateService,
        public http: HttpClient,
        private notify: NotificationService,
        public dialogRef: MatDialogRef<UsersImportComponent>,
        @Inject(MAT_DIALOG_DATA) public data: any,
    ) {
    }

    ngOnInit(): void { }

    changeColumn(coldb: string, colCsv: string) {
        console.log(coldb);
        console.log(colCsv);

        this.userData = [];
        for (let index = 0; index < 10; index++) {
            const data = this.csvData[index];
            this.userData.push({
                'id': coldb === 'id' ? data[this.csvColumns.filter(col => col === colCsv)[0]] : data[this.associatedColmuns['id']],
                'userId': coldb === 'userId' ? data[this.csvColumns.filter(col => col === colCsv)[0]] : data[this.associatedColmuns['userId']],
                'firstname': coldb === 'firstname' ? data[this.csvColumns.filter(col => col === colCsv)[0]] : data[this.associatedColmuns['firstname']],
                'lastname': coldb === 'lastname' ? data[this.csvColumns.filter(col => col === colCsv)[0]] : data[this.associatedColmuns['lastname']],
                'phone': coldb === 'phone' ? data[this.csvColumns.filter(col => col === colCsv)[0]] : data[this.associatedColmuns['phone']],
                'email': coldb === 'email' ? data[this.csvColumns.filter(col => col === colCsv)[0]] : data[this.associatedColmuns['email']]
            });
        }

        this.dataSource = new MatTableDataSource(this.userData);
    }

    uploadCsv(fileInput: any) {
        if (fileInput.target.files && fileInput.target.files[0]) {
            let rawCsv = [];
            const reader = new FileReader();

            reader.readAsText(fileInput.target.files[0]);

            reader.onload = (value: any) => {
                console.log(value.target.result.split('\n'));
                rawCsv = value.target.result.split('\n');
                this.csvColumns = rawCsv[0].split(';').map(s => s.replace(/"/gi, '').trim());

                let dataCol = [];
                let objData = {};
                for (let index = 1; index < rawCsv.length; index++) {
                    objData = {};
                    dataCol = rawCsv[index].split(';').map(s => s.replace(/"/gi, '').trim());
                    dataCol.forEach((element: any, index2: number) => {
                        objData[this.csvColumns[index2]] = element;
                    });
                    this.csvData.push(objData);
                }
                this.initData();
            };
        }
    }

    initData() {
        if (this.csvColumns.length >= this.userColmuns.length) {
            for (let index = 0; index < 10; index++) {
                const data = this.csvData[index];
                this.associatedColmuns['id'] = this.csvColumns[0];
                this.associatedColmuns['userId'] = this.csvColumns[1];
                this.associatedColmuns['firstname'] = this.csvColumns[2];
                this.associatedColmuns['lastname'] = this.csvColumns[3];
                this.associatedColmuns['phone'] = this.csvColumns[4];
                this.associatedColmuns['email'] = this.csvColumns[5];

                this.userData.push({
                    'id': data[this.csvColumns[0]],
                    'userId': data[this.csvColumns[1]],
                    'firstname': data[this.csvColumns[2]],
                    'lastname': data[this.csvColumns[3]],
                    'phone': data[this.csvColumns[4]],
                    'email': data[this.csvColumns[5]]
                });
            }
            this.dataSource = new MatTableDataSource(this.userData);
        } else {
            alert('Pas assez de données');
        }
    }
}
