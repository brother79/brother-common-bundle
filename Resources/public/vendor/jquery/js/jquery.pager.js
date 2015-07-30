(function($){
	$.fn.pager = function(options) {
		var settings = {		
			per_page:10, // кол-во на странице
			per_page_values:[10,20,30,50,100], // список для выбора
			start:1,     // первая запись
			page:1, // первая страница
			browse_remote:null, // урл для получения данных
			comment_id:null, // селектор для блока с комментарием
			per_page_id:null, // селектор для комбобокса с кол-вом на странице
			link_coment_id:null,// Страница № из ...
			links_id:null,// Ссылки на страницы
			result_id:null,// ссылка на рез-таты
			apply_id:null,// Применть фильтр
			clear_id:null,// очистить фильтр
			fields_id:null,// поля для фильтра
			form_id:null,// id для формы
			on_result:null,// обработчик на возврате рез-тат
			// render комментария
			render_comment: function(store){
				if (this.comment_id) {
					$(this.comment_id).html('Записи с '+(this.start)+' по '+(this.start+store.items.length-1) + ' из '+store.totalCount + ' найденных');
				}
			},
			// render комбобокса с кол-вом на странице
			render_per_page:function(){
				if (this.per_page_id) {
					$(this.per_page_id).selectEmpty()
						.selectAdd({values:this.per_page_values})
						.val(options.per_page);
				}	
			},
			// render коментов на страницы
			render_link_comment:function(){
				if (this.link_comment_id) {
					$(this.link_comment_id).html('станица '+this.page+' из '+this.page_count);
				}
			},
			// render список ссылок
			render_links:function(){
				$(this.links_id).html('');
				$.each(this.links, function(i,e){
					if (e == options.page) {
						$(options.links_id).append(options.page+'&nbsp;');
					} else {
						var link=e;
						switch(link) {
							case -2: link = options.first_text;break; 
							case -1: link = options.prev_text;break; 
							case options.page_count+1:link = options.next_text;break; 
							case options.page_count+2:link = options.last_text;break; 
						}	
						$(options.links_id).append('<a href="#" rel="'+e+'">'+link+'</a>').append('&nbsp;');
					}
				});

			},
			// рендерит рез-таты
			render_result:function(store){
				$(this.result_id).html('');
				$.each(store.items,function(i,a){
					$(options.result_id).append(options.format_item(i, a));	
				});
				if (options.on_result) {
					options.on_result();
				}
			},
			// рендерит 1 item
			format_item:function(i, item){
				return i;
			},
			// получить номера страниц для ссылок
			getLinks:function(){
				if (this.page_count == 0) return [];
				var result = [this.page];
				for (var i=1; i<3; i++) {
					if (this.page > 1000*i) result.push(this.page-1000*i);
					if (this.page > 100*i) result.push(this.page-100*i);
					if (this.page > 10*i) result.push(this.page-10*i);
					if (this.page > i) result.push(this.page-i);
					if (this.page <= this.page_count-i) result.push(this.page+i);
					if (this.page <= this.page_count-10*i) result.push(this.page+10*i);
					if (this.page <= this.page_count-100*i) result.push(this.page+100*i);
					if (this.page <= this.page_count-1000*i) result.push(this.page+1000*i);				
				}
				if (this.page>1) result.push(-1);
				if (this.page<this.page_count) result.push(this.page_count+1);
				var f=1, l=1;
				$.each(result, function(i,e){ 
					f = f && e!=1; 
					l = l && e != options.page_count;				
				});
				if (f) result.push(-2);
				if (l) result.push(this.page_count+2)
				result.sort(function(a,b){
					return a-b;
				});
				return result;			
			},
			// обновить пагер
			update:function(){
				if (options.browse_remote) {
					var url = options.browse_remote + (options.extra_params == '' ? '' : (options.browse_remote.search(/\?/)<0 ? '?' : '&') + options.extra_params);
	
					$('#indicator').show();
	
					$.ajax({
						type: "GET",
						url: url,
						data: {start:options.start,limit:options.per_page,page:options.page},
						success: function(data){
							if (options.start > data.totalCount && options.start > 1) {
								options.start = 1;							
								settings.update();
								return false;
							}						
							// Вычисляем общее кол-во страниц
							options.page_count = Math.ceil(data.totalCount/options.per_page);
							// Вычисляем текущую страницу
							options.page = Math.ceil((options.start-1)/options.per_page) + 1;
							if (options.page > options.page_count) options.page =options.page_count;					
							options.links = options.getLinks(); // Вычисляем ссылки на страницы					
							options.render_comment(data); // рендерим комментарий к записям
							options.render_per_page(); // рендерим комбобокс с кол-вом на странице					
							options.render_link_comment(); // Рендерим комментарий к старницам
							options.render_links(); // Рендерим ссылки					
							options.render_result(data); // рендерим рез-ты
							// Ставим обработчик на ссылки
							$(options.links_id).children('a').click(function(a){
								var page = $(this).attr('rel');
								options.start = (page == -2 ? 1	: (page==-1 ? (options.page-2)*options.per_page+1 : (page == options.page_count + 1 ? options.page*options.per_page+1 : (page == options.page_count + 2 ? (options.page_count-1)*options.per_page+1 : (page-1)*options.per_page+1))));
								options.page = (page==-2?1:(page==-1?options.page-1:(page==options.page_count+1?options.page+1 :(page==options.page_count+2?options.page_count-1:page))));
								options.update();
								return false;
							});	
						},
						complete:function(){
							$('#indicator').hide();
						},										
						dataType: "json"
					});
				}
			},
			submit:function(o){
				o = $.extend({extra_params:''}, o || {});	
				options.extra_params = o.extra_params;
				$(options.fields_id).each(function (i,e){			
				    if ($(e).val() != '') {
						options.extra_params += $(e).attr('name')+'='+$(e).val()+"&";
				    }	
				});
				options.update();
				return false;
			},
			// Текст на пред. старницу
			first_text: '&laquo;',
			prev_text: '&lt;',
			// Текст на след. страницу
			next_text: '&gt;',
			last_text: '&raquo;',
			extra_params:''
		}
		options = $.extend(settings, options || {});	
		if (options.page > 1) {
			options.start = (options.page - 1) * options.per_page + 1
		}
		$(options.per_page_id).change(function(e){
			options.per_page=$(this).val();
			options.update();
		});
		$(options.clear_id).click(function(e){
			$(options.fields_id).filter('[type=text]').val('');
			options.submit({extra_params:'_reset=1'});	
			return false;
		});
		$(options.apply_id).click(function(e){
			options.submit(null);	
			return false;
		});
		$(options.form_id).submit(options.submit);
		options.submit(null);
		return this;
	}

})(jQuery);
