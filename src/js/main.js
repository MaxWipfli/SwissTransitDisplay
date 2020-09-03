var app = new Vue({
    el: '#app',
    data() {
        return {
            stop: {},
            departures: [],
            time: {
                hours: moment().format('HH'),
                minutes: moment().format('mm'),
                blink: false
            }
        }
    },
    methods: {
        queryDepartures() {
            axios.get('/api.php').then(response => {
                this.stop = response.data.stop;
                this.departures = response.data.departures;
            });
        }
    },
    mounted() {            
        this.queryDepartures();

        setInterval(() => {
            let time = moment();
            this.time.hours = time.format('HH');
            this.time.minutes = time.format('mm');
            this.time.blink = !this.time.blink;

            if (time.seconds() % 10 == 0) {
                this.queryDepartures();
            }
        }, 1000);
    }
});