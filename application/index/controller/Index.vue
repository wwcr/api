<!--页面css样式-->
<style rel="stylesheet/less" type="text/less" lang="less" scoped>
    body, html {
        background: #f4f4f4;
    }

    .w90 {
        width: 90%
    }

    .banner {
        width: 100%;
    }

    .banner img {
        width: 100%;
    }

    .center {
        width: 100%;
        background: #fff;
    }

    .center .seek {
        height: 2.48rem;
        border: 1px solid #f1f1f1;
        line-height: 2.48rem;
    }

    .center .seek img {
        width: 1.3rem;
        height: 1.3rem;
        float: left;
        margin-top: 0.6rem;
        margin-left: 3.5rem;
        margin-right: 0.4rem;
    }

    .center .seek p {
        font-size: 0.44rem;
        color: #1E1E1E;
    }

    .serve {
        height: 3.05rem;
        line-height: 3.05rem;
    }

    .serve ul li {
        width: 49.5%;
        border-right: 1px solid #f1f1f1;
        float: left;
    }

    .serve ul li img {
        width: 1.3rem;
        height: 1.3rem;
        float: left;
        margin: 0.8rem 0.4rem 0.8rem 0.8rem;
    }

    .serve ul li p {
        font-size: 0.44rem;
        color: #1E1E1E;
    }

    .serve ul li:last-child {
        border-right: none;
    }

    .lism {
        background: #fdfdfd;
        margin: 0 0.5rem 0 0.5rem;
        border-bottom: 1px solid #f1f1f1;
        padding: 0.5rem 0;
    }

    .img_left {
        float: left;
    }

    .img_left img {
        width: 0.7rem;
        height: 0.7rem;
    }

    .div_left {
        float: left;
        margin-left: 0.4rem;
        margin-top: 0.2rem;
    }

    .div_left p {
        font-size: 0.28rem;
        color: #828282;
    }

    .fiv_right {
        float: right;
        margin-right: 0.3rem;
    }

    .fiv_right img {
        width: 0.36rem;
    }

    .lism_cen {
        margin: 0.1rem 0 0.1rem;
    }

    .lism_cen dl dt {
        width: 23%;
        float: right;
    }

    .lism_cen dl dt img {

        width: 2.2rem;
        height: 1.5rem;
    }

    .lism_cen dl dd {
        width: 70%;
        font-size: 0.38rem;
        color: #282828;
        margin-bottom: 0.3rem;
    }

    .bianji {
        color: #999;
        font-size: 0.4rem;
        float: left;
    }

    .more {
        color: #999;
        font-size: 0.38rem;
        padding: 0.3rem;
        text-align: center;
        background: #f9f9f9;
    }

    .articleBox {
        margin-top: 0.3rem;
        background: #fff;
    }

    .message {
        width: 0.5rem;
        height: 0.5rem;
        float: right;
    }

    .lism_div {
        margin-top: 0.3rem;
    }
    .banner img{
        cursor: pointer;
    }
    .yo-scroll{
        top: 0px !important;
    }
    .cen_box{
        background:#fff;
    }
    .article-box{
        padding-bottom: 2.2rem;
    }
   .nullData{
    margin-top: -3rem !important;
   }
   .swiper-container-horizontal > .swiper-scrollbar {
        position: absolute;
        left: 1%;
        bottom: 3px;
        z-index: 50;
        height: 0px;
        width: 0%;
    }

</style>
<template>
    <div id="index">
       <v-scroll ref='aaa' :on-refresh="onRefresh" :on-infinite="onInfinite" :dataList="scrollData">
            <div class="banner"><!-- 轮播图 -->
                <swiper :options="swiperOption" ref="mySwiperA">
                    <swiper-slide v-for="(x, ind) in banner_list" @click.native="DetailBanner(x.url)">
                        <img :src='host+x.banner'>
                    </swiper-slide>
                    <div class="swiper-pagination"  slot="pagination"></div>
                    <div class="swiper-scrollbar"   slot="scrollbar"></div>
                </swiper> 
            </div>
            <div class="center">
                <div class="seek" @click="hrefs('findcar')">
                    <img src="../../src/assets/img/icon1.png">
                    <p>寻车定位</p>
                </div>
                <div class="serve">
                    <ul>
                        <!-- <li @click="hrefs('forum')">
                        <li @click="hrefs('order/list')">
                            <img src="../../src/assets/img/zhixingyew.png">
                            <p>回收业务</p>
                        </li> -->
                        <li @click="hrefs('information')">
                        <!-- <li @click="hrefs('order/list')"> -->
                            <img src="../../src/assets/img/icon2_2.png">
                            <p>业务资讯</p>
                        </li>
                        <li @click="hrefs('vehicletool')">
                            <img src="../../src/assets/img/zhushoun3.png">
                            <p>车融助手</p>
                        </li>
                    </ul>
                </div>
            </div>
            
            <div slot="top" class="mint-loadmore-top">
              <span v-show="topStatus !== 'loading'" :class="{ 'rotate': topStatus === 'drop' }" style="font-size:.5rem;" >↓</span>
              <span v-show="topStatus === 'loading'"style="font-size:.5rem;">Loading...</span>
            </div>
            <div class="article-box">
                <div class="cen_box" v-for="x in article">
                    <div class="lism">
                        <div class="lism_cen clearfix">
                            <a @click="acticleDetails(x.article_id)">
                                <dl>
                                    <dt><img :src="host+x.art_thumb||'/static/img/img_13.28f6603.jpg'"></dt>
                                    <dd>
                                        {{x.art_title}}
                                        <div class="lism_div">
                                            <p class="bianji">编辑：{{x.ad_name}}&nbsp;{{len(x.art_time)}}</p>
                                        </div>

                                    </dd>
                                </dl>
                            </a>

                        </div>
                    </div>
                </div>
            </div>
                
                <!-- <i class="fa fa-bar-chart" aria-hidden="true"></i> -->
                <!-- <div class="more" @click="more">{{moreThatText()}}</div> -->
                
            <div slot="bottom" class="mint-loadmore-bottom">
                <span v-show="bottomStatus === 'drop'"style="font-size:.5rem;margin-top:-10px;">Loading...</span>
            </div>
        </v-scroll>
        <navcate></navcate>
    </div>
<!-- f0f4fa -->
    <!--首页-->
</template>
<script>
    import { Loadmore } from 'mint-ui';
    import units from '../tools/units'
    import cookie from '../tools/cookie'
    import navcate from './template/navcate.vue'
    import Vue from 'vue'
    import { swiper, swiperSlide } from 'vue-awesome-swiper'
    Vue.component(Loadmore.name, Loadmore);
    export default {
        name: 'index',
        data () {
            return {
                article: [],
                topStatus: '',
                bottomStatus: '',
                banner_list:[],
                articlePage: 0,
                articleLimit: 3,
                allarticle:'',
                distance:0,
                scrollTop: 0,//滚动的位置
                allLoaded:false,
                moreThat: true,
                host: units.getHost(),
                wrapperHeight:0,
                swiperOption: {
                    autoplay: 5000,
                    // loop: true,
                    pagination: '.swiper-pagination',
                    onSlideChangeEnd: swiper => {},
                    // direction: 'horizontal',
                    grabCursor: true,
                    setWrapperSize: true,
                    paginationClickable: true,
                    autoplayDisableOnInteraction : false,
                    observeParents: true
                },
                articlePage: 0,
                articleLimit: 2,
                allarticle:'',
                counter: 1, //当前页
                num: 2, // 一页显示多少条
                pageStart: 0, // 开始页数
                pageEnd: 0, // 结束页数
                listdata: [], // 下拉更新数据存放数组
                scrollData: {
                    noFlag: false //暂无更多数据显示
                }
            }
        },
        computed: {
            swiper() {
                return this.$refs.mySwiperA.swiper
            }
        },
        components: {
            navcate,
            swiper,
            swiperSlide,
            'v-scroll': require("./pull-refresh_main")
        },
        methods: {
            hrefs:function ($url) {
                if(cookie.get('user_auth') == 0 ) {
                    layer.msg('请先进行资料认证')
                    return false;
                }
                if(units.isLogin(true)){
                    location.href = '#/'+$url;
                }
            },
            acticleDetails (id) {//点击新闻详情页  
                location.href='#/article/content?id='+id
                this.scrollTop = $(".yo-scroll").scrollTop()
                cookie.set('scrollTop', $(".yo-scroll").scrollTop())
            },
            DetailBanner (ind) {
                 this.$router.push(ind);
                // if(ind == 0) {
                //     this.$router.push('/details1')
                // }else if (ind == 1) {
                //     this.$router.push('/details2')
                // } else if (ind == 2) {
                //     this.$router.push('/details3')
                // }
            },
            onRefresh(done) {
                this.getArticle(2);
                // this.getArticle(this.currentNum,1);
                done(); // call done

            },
             onInfinite(done) {
                // console.log(1323);
                this.getArticle(3);
                this.counter++;
                let end = this.pageEnd = this.num * this.counter;
                let i = this.pageStart = this.pageEnd - this.num;
                // console.log()
                let more = this.$el.querySelector('.load-more-main');
                // console.log(more);
                for(i; i < end; i++) {
                    if(i >= 30) {
                        // console.log(more);
                        more.style.display = 'none'; //隐藏加载条
                        //走完数据调用方法
                        this.scrollData.noFlag = true;
                        // console.log(111321231);
                        break;
                    } else {
                        this.listdata.push({
                            date: "2017-06-1"+i,
                            portfolio: "1.5195"+i,
                            drop: i+"+.00 %" ,
                            state: 2
                        })
                        more.style.display = 'none'; //隐藏加载条
                         // console.log(111);
                    }
                }
                
                done();
            },
            islogin(){},
            Goto: function (url, login) {
                if (login && !units.isLogin()) {
                    location.href = '/login'
                } else {
                    location.href = url;
                }
            },
            timeer: function (time) {
                return units.timemake(time)
            },
            len: function (a) {
                return a.substring(0, 10)
            },
            more(){
                this.getArticle();
            },
            moreThatText: function () {
                return this.moreThat == true ? '查看更多' : '全部加载完成';
            },
             handleTopChange(status) {
                this.topStatus = status;
                
              },
              handleBottomChange(status){
                this.bottomStatus = status;
                // console.log(this.bottomStatus);
              },
            getArticle(id){
                let that = this;
                let list = [];
                 if(id == 2){//下拉刷新
                    that.articlePage = 0;
                }else if(id == 3){//上拉加载
                     that.articlePage = that.articlePage + that.articleLimit;
                    if(that.articlePage + that.articleLimit > that.allarticle){
                        // more.style.display = 'none'; //隐藏加载条
                        //走完数据调用方法
                        that.scrollData.noFlag = true;
                    }
                }
                // console.log(that.scrollData.noFlag);
                // console.log(that.allarticle);
                // console.log(that.articlePage + that.articleLimit);
                this.$http.post(units.host('indexArticle_page'), {
                    page: that.articlePage,
                    limit:that.articleLimit+that.articlePage,
                }).then(function (res) {
                    // console.log(id);
                    // console.log(res.body.msg);
                    that.allarticle = res.body.msg;//总条数
                     that.article = res.body.info;
                    //  if(id == 2){//下拉刷新
                    //     that.article = [];
                    //     that.allLoaded = false;
                        
                    //  }
                    // for (let x in list) {
                    //     that.article.push(list[x]);
                    // }
                });
            },
            loadBottom() {
              this.getArticle(1);//代表上拉加载
              this.$refs.loadmore.onBottomLoaded();
             },
            loadTop() {
                  this.getArticle(2);
                  this.$refs.loadmore.onTopLoaded();
            },
            getbanner(){
                let that = this;
                this.$http.post(units.host('get_banner'), {
                }).then(function (res) {
                    // console.log(res);
                    that.banner_list = res.body.info;
                });
            }
        },
        activated () {
            $(".yo-scroll").scrollTop(this.scrollTop)
        },
        created: function () {
            let that = this;
            that.articlePage = 0;
            this.getArticle();
            this.getbanner();
            this.distance = 0;
            cookie.set('curr',0);
            units.title.set('无维金融');
           if(window.navigator.onLine==true){  
                //未联网状态跳转错误页

            }
            // this.$http.post(units.host('get_single_contentlist','api'), {
            //     uid: cookie.get('user_id')
            // }).then(function (res) {
            //     console.log(res)
            // })
        },
    }
</script>
