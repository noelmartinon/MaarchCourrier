import { Pipe } from '@angular/core';
import { LatinisePipe } from 'ngx-pipes';
import { FunctionsService } from '@service/functions.service';

@Pipe({ name: 'highlight' })
export class HighlightPipe {

    constructor(
        private latinisePipe: LatinisePipe,
        public functions: FunctionsService
    ) { }

    transform(text: string, args: string = '') {
        const index = text.indexOf(args);
        if (index >= 0) {
            text = text.substring(0, index) + '<span class=\'highlightResult\'>' + text.substring(index, index + args.length) + '</span>' + text.substring(index + args.length);
        }
        console.log(text);
        return text;
    }
}
